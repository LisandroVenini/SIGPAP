<?php

namespace App\Modules\Compras\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Compras\Mail\OrdenDeCompraMail;
use App\Modules\Compras\Models\Cotizacion;
use App\Modules\Compras\Models\LineaOrdenCompra;
use App\Modules\Compras\Models\OrdenDeCompra;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Models\SolicitudDeCotizacion;
use App\Modules\Compras\Queries\OrdenesDeCompraQuery;
use App\Modules\Compras\Queries\SolicitudesDeCotizacionQuery;
use App\Modules\Inventario\Services\GestionArticulosService;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\UsuariosService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GestionOrdenesDeCompraService
{
    private const ESTADO_ENVIADA = 'Enviada';

    private const ESTADO_ADJUDICADA = 'Adjudicada';

    private const ESTADO_EMITIDA = 'Emitida';

    private const ESTADO_CONFIRMADA = 'Confirmada';

    private const ESTADO_RECHAZADA = 'Rechazada';

    private const ESTADO_ANULADA = 'Anulada';

    public function __construct(
        private readonly AuditoriaService $auditoria,
        private readonly UsuariosService $usuarios,
        private readonly OrdenesDeCompraQuery $ordenesQuery,
        private readonly SolicitudesDeCotizacionQuery $solicitudesQuery,
        private readonly GestionArticulosService $articulos,
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
        return $this->ordenesQuery->listar($filtros);
    }

    /**
     * Return the sent solicitudes that received quotes, with the data needed to compare them.
     *
     * @return Collection<int, array{id: int, fecha: string, fecha_vencimiento: string, cotizaciones: array<int, array{id: int, proveedor: string, total: float, ultima_fecha_prometida: string|null}>}>
     */
    public function solicitudesConCotizaciones(): Collection
    {
        return $this->solicitudesQuery->conCotizaciones()
            ->map(function (SolicitudDeCotizacion $solicitud): array {
                $cotizaciones = $solicitud->cotizaciones->sortBy('id')->values();

                return [
                    'id' => $solicitud->id,
                    'fecha' => $solicitud->fecha->format('Y-m-d'),
                    'fecha_vencimiento' => $solicitud->fecha_vencimiento->format('Y-m-d'),
                    'cotizaciones' => $cotizaciones->map(fn (Cotizacion $cotizacion): array => [
                        'id' => $cotizacion->id,
                        'proveedor' => $cotizacion->proveedor->razon_social,
                        'total' => (float) $cotizacion->lineas->sum(
                            fn ($linea): float => (float) $linea->cantidad_ofertada * (float) $linea->precio_unitario,
                        ),
                        'ultima_fecha_prometida' => $cotizacion->lineas
                            ->max(fn ($linea): ?string => $linea->fecha_prometida?->toDateString()),
                    ])->values()->all(),
                ];
            })
            ->values();
    }

    public function emitir(Usuario $actor, int $cotizacionId, ?string $direccionIp = null): OrdenDeCompra
    {
        [$orden, $proveedor, $token] = DB::transaction(function () use ($actor, $cotizacionId, $direccionIp): array {
            $cotizacion = Cotizacion::query()
                ->with(['proveedor', 'solicitudDeCotizacion', 'lineas'])
                ->whereKey($cotizacionId)
                ->lockForUpdate()
                ->firstOrFail();

            $solicitud = $cotizacion->solicitudDeCotizacion;

            if ($solicitud === null) {
                throw ValidationException::withMessages([
                    'cotizacion' => 'La cotización no pertenece a una solicitud de cotización.',
                ]);
            }

            if ($solicitud->estado === self::ESTADO_ADJUDICADA) {
                throw ValidationException::withMessages([
                    'solicitud' => 'La solicitud ya fue adjudicada.',
                ]);
            }

            if ($solicitud->estado !== self::ESTADO_ENVIADA) {
                throw ValidationException::withMessages([
                    'solicitud' => 'La solicitud no está en estado Enviada.',
                ]);
            }

            $token = (string) Str::uuid();

            $orden = OrdenDeCompra::create([
                'numero_oc' => $this->siguienteNumeroOc(),
                'proveedor_id' => $cotizacion->proveedor_id,
                'cotizacion_id' => $cotizacion->id,
                'solicitud_de_disponibilidad_id' => null,
                'estado' => self::ESTADO_EMITIDA,
                'entrega_parcial' => false,
                'fecha_emision' => today()->toDateString(),
                'token' => $token,
            ]);

            foreach ($cotizacion->lineas as $linea) {
                $orden->lineas()->create([
                    'articulo_id' => $linea->articulo_id,
                    'cantidad' => $linea->cantidad_ofertada,
                    'precio_pactado' => $linea->precio_unitario,
                ]);
            }

            $cotizacion->lineas()->update(['adjudicada' => true]);

            $solicitud->update(['estado' => self::ESTADO_ADJUDICADA]);

            $this->auditoria->registrarEvento(
                $actor->id,
                'emitir_orden_compra',
                null,
                [
                    'numero_oc' => $orden->numero_oc,
                    'cotizacion_id' => $cotizacion->id,
                    'solicitud_de_cotizacion_id' => $solicitud->id,
                    'lineas' => $cotizacion->lineas->count(),
                ],
                $direccionIp,
                OrdenDeCompra::class,
                $orden->id,
            );

            return [$orden->load('lineas'), $cotizacion->proveedor, $token];
        });

        $this->enviarOrden($orden, $proveedor, $token);

        return $orden;
    }

    /**
     * @param  array<int, array{articulo_id: int, cantidad: float|int|string, precio_pactado: float|int|string}>  $lineas
     */
    public function actualizar(Usuario $actor, OrdenDeCompra $orden, array $lineas, ?string $direccionIp = null): void
    {

        DB::transaction(function () use ($actor, $orden, $lineas, $direccionIp): void {
            $orden = OrdenDeCompra::query()
                ->whereKey($orden->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($orden->estado !== self::ESTADO_EMITIDA) {
                throw ValidationException::withMessages([
                    'estado' => 'Solo se puede modificar una orden emitida.',
                ]);
            }

            $anterior = ['lineas' => $orden->lineas()->count()];

            $this->validarLineas($lineas);

            $orden->lineas()->delete();

            foreach ($lineas as $linea) {
                $orden->lineas()->create([
                    'articulo_id' => $linea['articulo_id'],
                    'cantidad' => $linea['cantidad'],
                    'precio_pactado' => $linea['precio_pactado'],
                ]);
            }

            $this->auditoria->registrarEvento(
                $actor->id,
                'actualizar_orden_compra',
                $anterior,
                ['lineas' => count($lineas)],
                $direccionIp,
                OrdenDeCompra::class,
                $orden->id,
            );
        });
    }

    public function anular(Usuario $actor, OrdenDeCompra $orden, ?string $motivo = null, ?string $direccionIp = null): void
    {
        DB::transaction(function () use ($actor, $orden, $motivo, $direccionIp): void {
            $orden = OrdenDeCompra::query()
                ->whereKey($orden->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($orden->estado !== self::ESTADO_EMITIDA) {
                throw ValidationException::withMessages([
                    'estado' => 'Solo se puede anular una orden emitida.',
                ]);
            }

            $anterior = ['estado' => $orden->estado];

            $orden->update(['estado' => self::ESTADO_ANULADA]);

            $this->auditoria->registrarEvento(
                $actor->id,
                'anular_orden_compra',
                $anterior,
                ['estado' => self::ESTADO_ANULADA, 'motivo' => $motivo],
                $direccionIp,
                OrdenDeCompra::class,
                $orden->id,
            );
        });
    }

    public function confirmar(string $token, bool $aceptada, ?string $motivo = null, ?string $direccionIp = null): void
    {
        DB::transaction(function () use ($token, $aceptada, $motivo, $direccionIp): void {
            $orden = OrdenDeCompra::query()
                ->where('token', $token)
                ->lockForUpdate()
                ->first();

            if ($orden === null || $orden->estado !== self::ESTADO_EMITIDA) {
                throw (new ModelNotFoundException)->setModel(OrdenDeCompra::class);
            }

            $usuarioId = $this->auditoria->usuarioResponsableDe(
                OrdenDeCompra::class,
                $orden->id,
                ['emitir_orden_compra'],
            );

            if ($usuarioId === null) {
                throw (new ModelNotFoundException)->setModel(OrdenDeCompra::class);
            }

            $anterior = ['estado' => $orden->estado];
            $nuevoEstado = $aceptada ? self::ESTADO_CONFIRMADA : self::ESTADO_RECHAZADA;

            $datos = [
                'estado' => $nuevoEstado,
                'token' => null,
            ];

            if ($aceptada) {
                $datos['fecha_confirmacion'] = today()->toDateString();
            }

            $orden->update($datos);

            $this->auditoria->registrarEvento(
                $usuarioId,
                'confirmar_orden_compra',
                $anterior,
                ['estado' => $nuevoEstado, 'motivo' => $motivo],
                $direccionIp,
                OrdenDeCompra::class,
                $orden->id,
            );
        });
    }

    /**
     * @return array{numero_oc: string, proveedor: string, fecha_emision: string, lineas: array<int, array{articulo: string, cantidad: float, precio_pactado: float}>, total: float}|null
     */
    public function obtenerPorToken(string $token): ?array
    {
        if ($token === '') {
            return null;
        }

        $orden = OrdenDeCompra::query()
            ->with(['proveedor', 'lineas'])
            ->where('token', $token)
            ->where('estado', self::ESTADO_EMITIDA)
            ->first();

        if ($orden === null) {
            return null;
        }

        return [
            'numero_oc' => $orden->numero_oc,
            'proveedor' => $orden->proveedor->razon_social,
            'fecha_emision' => $orden->fecha_emision->format('Y-m-d'),
            'lineas' => $this->lineasParaMostrar($orden),
            'total' => (float) $orden->lineas->sum(
                fn (LineaOrdenCompra $linea): float => (float) $linea->cantidad * (float) $linea->precio_pactado,
            ),
        ];
    }

    private function siguienteNumeroOc(): string
    {
        $prefijo = 'OC-'.today()->year.'-';

        $ultimo = OrdenDeCompra::query()
            ->where('numero_oc', 'like', $prefijo.'%')
            ->orderByDesc('numero_oc')
            ->lockForUpdate()
            ->value('numero_oc');

        $secuencia = $ultimo === null
            ? 1
            : ((int) substr($ultimo, strlen($prefijo))) + 1;

        return $prefijo.str_pad((string) $secuencia, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<int, array{articulo: string, cantidad: float, precio_pactado: float}>
     */
    private function lineasParaMostrar(OrdenDeCompra $orden): array
    {
        $orden->loadMissing('lineas');

        $resumenes = $this->articulos->resumenesDeArticulos(
            $orden->lineas->pluck('articulo_id')->all(),
        );

        return $orden->lineas
            ->map(fn (LineaOrdenCompra $linea): array => [
                'articulo' => $resumenes[$linea->articulo_id]['descripcion'] ?? 'Artículo #'.$linea->articulo_id,
                'cantidad' => (float) $linea->cantidad,
                'precio_pactado' => (float) $linea->precio_pactado,
            ])
            ->values()
            ->all();
    }

    private function enviarOrden(OrdenDeCompra $orden, Proveedor $proveedor, string $token): void
    {
        if (blank($proveedor->email)) {
            return;
        }

        $lineas = $this->lineasParaMostrar($orden);

        $total = $orden->lineas->sum(
            fn (LineaOrdenCompra $linea): float => (float) $linea->cantidad * (float) $linea->precio_pactado,
        );

        Mail::to($proveedor->email)->send(new OrdenDeCompraMail(
            numeroOc: $orden->numero_oc,
            proveedor: $proveedor->razon_social,
            lineas: $lineas,
            total: (float) $total,
            token: $token,
        ));
    }

    /**
     * Domain invariant: an order line cannot have a non-positive quantity or a negative price.
     *
     * @param  array<int, array{cantidad: float|string, precio_pactado: float|string}>  $lineas
     */
    private function validarLineas(array $lineas): void
    {
        foreach (array_values($lineas) as $indice => $linea) {
            if ((float) ($linea['cantidad'] ?? 0) <= 0) {
                throw ValidationException::withMessages([
                    "lineas.{$indice}.cantidad" => 'La cantidad debe ser mayor a 0.',
                ]);
            }

            if ((float) ($linea['precio_pactado'] ?? 0) < 0) {
                throw ValidationException::withMessages([
                    "lineas.{$indice}.precio_pactado" => 'El precio pactado no puede ser negativo.',
                ]);
            }
        }
    }
}
