<?php

namespace App\Modules\Compras\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Compras\Enums\EstadoSolicitudDeCotizacion;
use App\Modules\Compras\Mail\SolicitudDeCotizacionMail;
use App\Modules\Compras\Models\Cotizacion;
use App\Modules\Compras\Models\LineaCotizacion;
use App\Modules\Compras\Models\LineaSolicitudCotizacion;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Models\SolicitudDeCotizacion;
use App\Modules\Compras\Queries\LeadTimePorArticuloQuery;
use App\Modules\Compras\Queries\SolicitudesDeCotizacionQuery;
use App\Modules\Inventario\Services\GestionArticulosService;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\ParametroGlobalService;
use App\Modules\Usuarios\Services\UsuariosService;
use App\Shared\Alertas\Services\AlertaService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GestionSolicitudesDeCotizacionService
{
    public function __construct(
        private readonly AuditoriaService $auditoria,
        private readonly UsuariosService $usuarios,
        private readonly GestionArticulosService $articulos,
        private readonly SolicitudesDeCotizacionQuery $query,
        private readonly AlertaService $alertas,
        private readonly ParametroGlobalService $parametros,
        private readonly LeadTimePorArticuloQuery $leadTimes,
        private readonly GestionRankingService $ranking,
    ) {}

    public function puedeGestionar(Usuario $actor): bool
    {
        return $this->usuarios->autorizar($actor, 'compras.gestionar');
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function listar(array $filtros = []): LengthAwarePaginator
    {
        return $this->query->listar($filtros)->through(fn (array $fila): array => [
            ...$fila,
            'codigo' => $this->codigo((int) $fila['id'], (string) $fila['fecha']),
            'estado_visible' => $this->estadoVisible(
                (string) $fila['estado'],
                (int) $fila['cotizaciones'],
                Carbon::parse((string) $fila['fecha_vencimiento']),
            ),
            'respuestas' => [
                'recibidas' => (int) $fila['cotizaciones'],
                'esperadas' => (int) $fila['proveedores'],
            ],
        ]);
    }

    /**
     * Full detail of the given solicitudes: requested lines, invited suppliers,
     * received quotes with their line prices and the visible state.
     *
     * @param  array<int, int>  $solicitudIds
     * @return array<int, array<string, mixed>>
     */
    public function detallesDeSolicitudes(array $solicitudIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $solicitudIds)));

        if ($ids === []) {
            return [];
        }

        $solicitudes = SolicitudDeCotizacion::query()
            ->with(['lineas', 'proveedores', 'cotizaciones.proveedor', 'cotizaciones.lineas'])
            ->whereKey($ids)
            ->get();

        $articuloIds = $solicitudes
            ->flatMap(fn (SolicitudDeCotizacion $solicitud): array => $solicitud->lineas
                ->pluck('articulo_id')
                ->merge($solicitud->cotizaciones->flatMap(
                    fn (Cotizacion $cotizacion) => $cotizacion->lineas->pluck('articulo_id'),
                ))
                ->all())
            ->unique()
            ->values()
            ->all();

        $resumenes = $this->articulos->resumenesDeArticulos($articuloIds);

        $detalles = [];

        foreach ($solicitudes as $solicitud) {
            $detalles[$solicitud->id] = $this->detalleDeSolicitud($solicitud, $resumenes);
        }

        return $detalles;
    }

    /**
     * @return Collection<int, array{id: int, razon_social: string}>
     */
    public function proveedoresActivos(): Collection
    {
        return Proveedor::query()
            ->where('estado', 'Activo')
            ->orderBy('razon_social')
            ->get(['id', 'razon_social'])
            ->map(fn (Proveedor $proveedor): array => [
                'id' => $proveedor->id,
                'razon_social' => $proveedor->razon_social,
            ]);
    }

    /**
     * @param  array{fecha_vencimiento: string, lineas: array<int, array{articulo_id: int, cantidad_solicitada: float|int|string}>, proveedores: array<int, int>}  $datos
     */
    public function crear(Usuario $actor, array $datos, ?string $direccionIp = null): SolicitudDeCotizacion
    {
        [$solicitud, $enviosPendientes] = DB::transaction(function () use ($actor, $datos, $direccionIp): array {
            $proveedores = $this->proveedoresConEmail($datos['proveedores']);

            $solicitud = SolicitudDeCotizacion::create([
                'fecha' => today()->toDateString(),
                'fecha_vencimiento' => $datos['fecha_vencimiento'],
                'estado' => EstadoSolicitudDeCotizacion::Enviada->value,
            ]);

            $this->reemplazarLineas($solicitud, $datos['lineas']);

            $tokens = $this->sincronizarProveedores($solicitud, $datos['proveedores']);

            $this->auditoria->registrarEvento(
                $actor->id,
                'crear_solicitud_cotizacion',
                null,
                [
                    'fecha_vencimiento' => $datos['fecha_vencimiento'],
                    'lineas' => count($datos['lineas']),
                    'proveedores' => array_values($datos['proveedores']),
                ],
                $direccionIp,
                SolicitudDeCotizacion::class,
                $solicitud->id,
            );

            $enviosPendientes = $proveedores
                ->map(fn (Proveedor $proveedor): array => [
                    'proveedor' => $proveedor,
                    'token' => $tokens[$proveedor->id],
                ])
                ->values()
                ->all();

            return [$solicitud, $enviosPendientes];
        });

        $this->enviarInvitaciones($solicitud, $enviosPendientes);

        return $solicitud;
    }

    /**
     * @param  array{fecha_vencimiento: string, lineas: array<int, array{articulo_id: int, cantidad_solicitada: float|int|string}>, proveedores: array<int, int>}  $datos
     */
    public function actualizar(Usuario $actor, SolicitudDeCotizacion $solicitud, array $datos, ?string $direccionIp = null): void
    {
        [$solicitud, $enviosPendientes] = DB::transaction(function () use ($actor, $solicitud, $datos, $direccionIp): array {
            $solicitud = SolicitudDeCotizacion::query()
                ->whereKey($solicitud->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($solicitud->estado, [
                EstadoSolicitudDeCotizacion::Borrador->value,
                EstadoSolicitudDeCotizacion::Enviada->value,
            ], true)) {
                throw ValidationException::withMessages([
                    'estado' => 'Solo se puede modificar una solicitud en borrador o enviada.',
                ]);
            }

            if ($solicitud->cotizaciones()->exists()) {
                throw ValidationException::withMessages([
                    'solicitud' => 'La solicitud ya tiene cotizaciones cargadas y no puede modificarse.',
                ]);
            }

            $proveedoresPrevios = $solicitud->proveedores()->pluck('proveedores.id')->all();
            $proveedoresNuevosIds = array_values(array_diff($datos['proveedores'], $proveedoresPrevios));
            $proveedoresNuevos = $this->proveedoresConEmail($proveedoresNuevosIds);

            $anterior = [
                'fecha_vencimiento' => $solicitud->fecha_vencimiento->format('Y-m-d'),
                'lineas' => $solicitud->lineas()->count(),
                'proveedores' => $proveedoresPrevios,
            ];

            $solicitud->update([
                'fecha_vencimiento' => $datos['fecha_vencimiento'],
            ]);

            $solicitud->lineas()->delete();
            $this->reemplazarLineas($solicitud, $datos['lineas']);

            $tokens = $this->sincronizarProveedores($solicitud, $datos['proveedores']);

            $this->auditoria->registrarEvento(
                $actor->id,
                'actualizar_solicitud_cotizacion',
                $anterior,
                [
                    'fecha_vencimiento' => $datos['fecha_vencimiento'],
                    'lineas' => count($datos['lineas']),
                    'proveedores' => array_values($datos['proveedores']),
                ],
                $direccionIp,
                SolicitudDeCotizacion::class,
                $solicitud->id,
            );

            $enviosPendientes = $proveedoresNuevos
                ->map(fn (Proveedor $proveedor): array => [
                    'proveedor' => $proveedor,
                    'token' => $tokens[$proveedor->id],
                ])
                ->values()
                ->all();

            return [$solicitud, $enviosPendientes];
        });

        $this->enviarInvitaciones($solicitud, $enviosPendientes);
    }

    public function anular(Usuario $actor, SolicitudDeCotizacion $solicitud, ?string $motivo = null, ?string $direccionIp = null): void
    {
        DB::transaction(function () use ($actor, $solicitud, $motivo, $direccionIp): void {
            $solicitud = SolicitudDeCotizacion::query()
                ->whereKey($solicitud->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($solicitud->estado, [EstadoSolicitudDeCotizacion::Adjudicada->value, EstadoSolicitudDeCotizacion::Anulada->value], true)) {
                throw ValidationException::withMessages([
                    'estado' => 'La solicitud no puede anularse en su estado actual.',
                ]);
            }

            if ($solicitud->cotizaciones()->exists()) {
                throw ValidationException::withMessages([
                    'estado' => 'La solicitud tiene cotizaciones cargadas y no puede anularse.',
                ]);
            }

            $anterior = ['estado' => $solicitud->estado];

            $solicitud->update(['estado' => EstadoSolicitudDeCotizacion::Anulada->value]);

            $this->auditoria->registrarEvento(
                $actor->id,
                'anular_solicitud_cotizacion',
                $anterior,
                ['estado' => EstadoSolicitudDeCotizacion::Anulada->value, 'motivo' => $motivo],
                $direccionIp,
                SolicitudDeCotizacion::class,
                $solicitud->id,
            );
        });
    }

    /**
     * Create a draft request for an article at supply risk.
     *
     * The Inventario origin process calls this; it returns the new id or null
     * when a draft is already pending or there is nothing to request.
     */
    public function crearBorradorPorRiesgo(int $articuloId, float $cantidad): ?int
    {
        if ($cantidad <= 0 || $this->query->idDeBorradorAbiertoParaArticulo($articuloId) !== null) {
            return null;
        }

        return DB::transaction(function () use ($articuloId, $cantidad): ?int {
            // Re-check inside the transaction to avoid two concurrent runs.
            if ($this->query->idDeBorradorAbiertoParaArticulo($articuloId) !== null) {
                return null;
            }

            $vigencia = (int) $this->parametros->parametro('compras_vigencia_cotizacion_dias');

            $solicitud = SolicitudDeCotizacion::query()->create([
                'fecha' => today()->toDateString(),
                'fecha_vencimiento' => today()->addDays($vigencia > 0 ? $vigencia : 7)->toDateString(),
                'estado' => EstadoSolicitudDeCotizacion::Borrador->value,
            ]);

            $solicitud->lineas()->create([
                'articulo_id' => $articuloId,
                'cantidad_solicitada' => round($cantidad, 2),
            ]);

            $proveedores = $this->proveedoresSugeridosParaArticulo($articuloId);

            if ($proveedores !== []) {
                $solicitud->proveedores()->attach($proveedores);
            }

            return (int) $solicitud->id;
        });
    }

    /**
     * Id of the open draft covering the article, if any.
     */
    public function idDeBorradorAbiertoParaArticulo(int $articuloId): ?int
    {
        return $this->query->idDeBorradorAbiertoParaArticulo($articuloId);
    }

    /**
     * Turn a pending draft into a sent request for quotation.
     *
     * Used by the automatic licitation: it picks the best suppliers, generates
     * their tokens, moves the request to `Enviada`, audits the decision and
     * sends the invitations. It never fires twice thanks to the state check
     * under a row lock.
     *
     * @return array{disparada: bool, motivo: string|null, proveedores: int}
     */
    public function dispararLicitacion(int $solicitudId, int $actorId): array
    {
        $solicitud = SolicitudDeCotizacion::query()->with('lineas')->find($solicitudId);

        if ($solicitud === null) {
            return ['disparada' => false, 'motivo' => 'solicitud_inexistente', 'proveedores' => 0];
        }

        if ($solicitud->estado !== EstadoSolicitudDeCotizacion::Borrador->value) {
            return ['disparada' => false, 'motivo' => 'estado_invalido', 'proveedores' => 0];
        }

        $proveedores = $this->proveedoresParaLicitacion($solicitud);

        if ($proveedores === []) {
            return ['disparada' => false, 'motivo' => 'sin_proveedores_con_email', 'proveedores' => 0];
        }

        $envios = [];
        $disparada = false;

        DB::transaction(function () use ($solicitudId, $proveedores, $actorId, &$envios, &$disparada): void {
            $solicitud = SolicitudDeCotizacion::query()
                ->whereKey($solicitudId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($solicitud->estado !== EstadoSolicitudDeCotizacion::Borrador->value) {
                return;
            }

            $this->sincronizarProveedores($solicitud, $proveedores);

            $solicitud->update(['estado' => EstadoSolicitudDeCotizacion::Enviada->value]);

            $this->auditoria->registrarEvento(
                $actorId,
                'disparar_licitacion_automatica',
                ['estado' => EstadoSolicitudDeCotizacion::Borrador->value],
                ['estado' => EstadoSolicitudDeCotizacion::Enviada->value, 'proveedores' => count($proveedores)],
                null,
                SolicitudDeCotizacion::class,
                $solicitud->id,
            );

            $envios = $solicitud->proveedores()
                ->whereIn('proveedores.id', $proveedores)
                ->whereNotNull('proveedores.email')
                ->get()
                ->map(fn (Proveedor $proveedor): array => [
                    'proveedor' => $proveedor,
                    'token' => (string) $proveedor->pivot->token,
                ])
                ->values()
                ->all();

            $disparada = true;
        });

        if (! $disparada) {
            return ['disparada' => false, 'motivo' => 'estado_invalido', 'proveedores' => 0];
        }

        $this->enviarInvitaciones($solicitud->refresh(), $envios);

        return ['disparada' => true, 'motivo' => null, 'proveedores' => count($proveedores)];
    }

    /**
     * Best suppliers with email for one article: those that already quoted it,
     * ranked by performance. Never invents a supplier.
     *
     * @return array<int, int>
     */
    public function proveedoresSugeridosParaArticulo(int $articuloId): array
    {
        $candidatos = $this->leadTimes->resumen()['por_articulo'][$articuloId]['proveedor_ids'] ?? [];

        return $this->seleccionarProveedores($candidatos, [], $this->limiteDeProveedores());
    }

    private function limiteDeProveedores(): int
    {
        return max(1, (int) ($this->parametros->parametro('compras_proveedores_por_licitacion') ?? 3));
    }

    /**
     * @param  array<int, int>  $candidatos
     * @param  array<int, int>  $actuales
     * @return array<int, int>
     */
    private function seleccionarProveedores(array $candidatos, array $actuales, int $limite): array
    {
        $candidatos = array_values(array_unique(array_map('intval', $candidatos)));
        $puntajes = $this->ranking->puntajesPorProveedor($candidatos);

        if ($puntajes !== []) {
            uasort($puntajes, fn (array $a, array $b): int => $b['puntaje'] <=> $a['puntaje']);
            $ordenados = array_map('intval', array_keys($puntajes));
        } else {
            $ordenados = $candidatos;
        }

        $conEmail = Proveedor::query()
            ->whereIn('id', $ordenados)
            ->whereNotNull('email')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $seleccion = array_values(array_intersect($ordenados, $conEmail));
        $seleccion = array_values(array_unique(array_merge($actuales, $seleccion)));

        return array_slice($seleccion, 0, $limite);
    }

    /**
     * Best suppliers to invite for the draft lines.
     *
     * @return array<int, int>
     */
    private function proveedoresParaLicitacion(SolicitudDeCotizacion $solicitud): array
    {
        $leadTimes = $this->leadTimes->resumen()['por_articulo'];
        $candidatos = [];

        foreach ($solicitud->lineas as $linea) {
            $candidatos = array_merge(
                $candidatos,
                $leadTimes[(int) $linea->articulo_id]['proveedor_ids'] ?? [],
            );
        }

        $actuales = $solicitud->proveedores()
            ->whereNotNull('email')
            ->pluck('proveedores.id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        return $this->seleccionarProveedores($candidatos, $actuales, $this->limiteDeProveedores());
    }

    private function reemplazarLineas(SolicitudDeCotizacion $solicitud, array $lineas): void
    {
        foreach ($lineas as $linea) {
            $solicitud->lineas()->create([
                'articulo_id' => $linea['articulo_id'],
                'cantidad_solicitada' => $linea['cantidad_solicitada'],
            ]);
        }
    }

    /**
     * @param  array<int, int>  $proveedores
     * @return array<int, string> token keyed by provider id
     */
    private function sincronizarProveedores(SolicitudDeCotizacion $solicitud, array $proveedores): array
    {
        $existentes = $solicitud->proveedores()->get()
            ->mapWithKeys(fn (Proveedor $proveedor): array => [$proveedor->id => $proveedor->pivot->token])
            ->all();

        $tokensNuevos = [];
        $sincronizar = [];

        foreach ($proveedores as $proveedorId) {
            $token = $existentes[$proveedorId] ?? null;

            if ($token === null) {
                $token = (string) Str::uuid();
                $tokensNuevos[$proveedorId] = $token;
            }

            $sincronizar[$proveedorId] = ['token' => $token];
        }

        $solicitud->proveedores()->sync($sincronizar);

        // Only providers that did not have a link yet are returned, so the caller mails just those.
        return $tokensNuevos;
    }

    /**
     * @param  array<int, int>  $ids
     * @return Collection<int, Proveedor>
     */
    private function proveedoresConEmail(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        $proveedores = Proveedor::query()->whereKey($ids)->get();

        if ($proveedores->count() !== count(array_unique($ids))) {
            throw ValidationException::withMessages([
                'proveedores' => 'Alguno de los proveedores seleccionados no existe.',
            ]);
        }

        $inactivo = $proveedores->first(fn (Proveedor $proveedor): bool => $proveedor->estado !== 'Activo');

        if ($inactivo !== null) {
            throw ValidationException::withMessages([
                'proveedores' => "El proveedor {$inactivo->razon_social} no está activo.",
            ]);
        }

        $sinEmail = $proveedores->first(fn (Proveedor $proveedor): bool => blank($proveedor->email));

        if ($sinEmail !== null) {
            throw ValidationException::withMessages([
                'proveedores' => "El proveedor {$sinEmail->razon_social} no tiene un email registrado.",
            ]);
        }

        return $proveedores->keyBy('id');
    }

    /**
     * @param  array<int, array{proveedor: Proveedor, token: string}>  $envios
     */
    private function enviarInvitaciones(SolicitudDeCotizacion $solicitud, array $envios): void
    {
        if ($envios === []) {
            return;
        }

        $solicitud->loadMissing('lineas');

        $resumenes = $this->articulos->resumenesDeArticulos(
            $solicitud->lineas->pluck('articulo_id')->all(),
        );

        $lineas = $solicitud->lineas
            ->map(fn (LineaSolicitudCotizacion $linea): array => [
                'articulo' => $resumenes[$linea->articulo_id]['descripcion'] ?? 'Artículo #'.$linea->articulo_id,
                'cantidad_solicitada' => (float) $linea->cantidad_solicitada,
            ])
            ->values()
            ->all();

        foreach ($envios as $envio) {
            try {
                Mail::to($envio['proveedor']->email)->send(
                    new SolicitudDeCotizacionMail(
                        $solicitud,
                        $envio['proveedor'],
                        $envio['token'],
                        $lineas,
                    ),
                );
            } catch (\Throwable $excepcion) {
                Log::error('No se pudo enviar la invitación de cotización.', [
                    'solicitud_de_cotizacion_id' => $solicitud->id,
                    'proveedor_id' => $envio['proveedor']->id,
                    'error' => $excepcion->getMessage(),
                ]);

                $this->alertas->emitirPorTipo('mail_fallido', [
                    'numero_oc' => 'Solicitud #'.$solicitud->id,
                    'proveedor' => $envio['proveedor']->razon_social,
                    'error' => $excepcion->getMessage(),
                    'solicitud_de_cotizacion_id' => $solicitud->id,
                ], 'solicitud:'.$solicitud->id.':proveedor:'.$envio['proveedor']->id);
            }
        }
    }

    /**
     * @param  array<int, array{descripcion: string}>  $resumenes
     * @return array<string, mixed>
     */
    private function detalleDeSolicitud(SolicitudDeCotizacion $solicitud, array $resumenes): array
    {
        $descripcion = fn (int $articuloId): string => $resumenes[$articuloId]['descripcion'] ?? '';
        $pedidoPorArticulo = $solicitud->lineas->keyBy('articulo_id');

        $cotizaciones = $solicitud->cotizaciones
            ->sortBy('id')
            ->map(function (Cotizacion $cotizacion) use ($descripcion, $pedidoPorArticulo): array {
                $lineas = $cotizacion->lineas
                    ->map(function (LineaCotizacion $linea) use ($descripcion, $pedidoPorArticulo): array {
                        return [
                            'articulo' => $descripcion((int) $linea->articulo_id),
                            'precio_unitario' => (float) $linea->precio_unitario,
                            'cantidad_solicitada' => (float) ($pedidoPorArticulo->get($linea->articulo_id)?->cantidad_solicitada ?? 0),
                            'cantidad_ofertada' => (float) $linea->cantidad_ofertada,
                            'fecha_prometida' => $linea->fecha_prometida?->toDateString(),
                            'subtotal' => round((float) $linea->cantidad_ofertada * (float) $linea->precio_unitario, 2),
                        ];
                    })
                    ->values()
                    ->all();

                $fechasPrometidas = array_filter(array_column($lineas, 'fecha_prometida'));

                return [
                    'id' => (int) $cotizacion->id,
                    'proveedor' => $cotizacion->proveedor?->razon_social ?? '',
                    'fecha' => $cotizacion->fecha?->toDateString(),
                    'total_ofertado' => round((float) array_sum(array_column($lineas, 'subtotal')), 2),
                    'costo_pedido' => round((float) array_sum(array_map(
                        fn (array $linea): float => $linea['cantidad_solicitada'] * $linea['precio_unitario'],
                        $lineas,
                    )), 2),
                    'fecha_prometida' => $fechasPrometidas === [] ? null : max($fechasPrometidas),
                    'lineas' => $lineas,
                ];
            })
            ->values()
            ->all();

        return [
            'id' => (int) $solicitud->id,
            'codigo' => $this->codigo((int) $solicitud->id, $solicitud->fecha->toDateString()),
            'fecha' => $solicitud->fecha->toDateString(),
            'fecha_vencimiento' => $solicitud->fecha_vencimiento->toDateString(),
            'estado' => $solicitud->estado,
            'estado_visible' => $this->estadoVisible(
                $solicitud->estado,
                count($cotizaciones),
                $solicitud->fecha_vencimiento,
            ),
            'insumos' => $this->resumenDeInsumos($solicitud->lineas, $descripcion),
            'lineas' => $solicitud->lineas
                ->map(fn (LineaSolicitudCotizacion $linea): array => [
                    'articulo_id' => (int) $linea->articulo_id,
                    'articulo' => $descripcion((int) $linea->articulo_id),
                    'cantidad_solicitada' => (float) $linea->cantidad_solicitada,
                ])
                ->values()
                ->all(),
            'proveedores' => $solicitud->proveedores
                ->sortBy('razon_social')
                ->map(function (Proveedor $proveedor) use ($solicitud): array {
                    $pivot = $proveedor->pivot;

                    return [
                        'id' => (int) $proveedor->id,
                        'razon_social' => $proveedor->razon_social,
                        'enlace_vigente' => $pivot !== null && $pivot->token !== null,
                        'respondio' => $solicitud->cotizaciones->contains('proveedor_id', $proveedor->id),
                    ];
                })
                ->values()
                ->all(),
            'cotizaciones' => $cotizaciones,
        ];
    }

    private function codigo(int $id, string $fecha): string
    {
        return sprintf('SC-%s-%04d', substr($fecha, 0, 4), $id);
    }

    private function estadoVisible(string $estado, int $cotizaciones, Carbon $vencimiento): string
    {
        if ($estado === EstadoSolicitudDeCotizacion::Enviada->value) {
            if ($cotizaciones > 0) {
                return 'Respondida';
            }

            if ($vencimiento->lt(today())) {
                return 'Vencida';
            }
        }

        return $estado;
    }

    /**
     * @param  Collection<int, LineaSolicitudCotizacion>  $lineas
     * @param  callable(int): string  $descripcion
     */
    private function resumenDeInsumos(Collection $lineas, callable $descripcion): string
    {
        $nombres = $lineas
            ->map(fn (LineaSolicitudCotizacion $linea): string => $descripcion((int) $linea->articulo_id))
            ->filter()
            ->values();

        if ($nombres->isEmpty()) {
            return '';
        }

        if ($nombres->count() === 1) {
            return (string) $nombres->first();
        }

        return $nombres->first().' +'.($nombres->count() - 1);
    }
}
