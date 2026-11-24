<?php

namespace App\Modules\Compras\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Compras\Enums\EstadoSolicitudDeDisponibilidad;
use App\Modules\Compras\Mail\SolicitudDeDisponibilidadMail;
use App\Modules\Compras\Models\Cotizacion;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Models\SolicitudDeDisponibilidad;
use App\Modules\Compras\Queries\PreciosDeReferenciaQuery;
use App\Modules\Inventario\Services\GestionArticulosService;
use App\Modules\Predictivo\Services\ResolverDisponibilidadService;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\ParametroGlobalService;
use App\Modules\Usuarios\Services\UsuariosService;
use App\Shared\Alertas\Services\AlertaService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * CU-COM-19: request availability from a single supplier (flow A).
 *
 * The supplier answers through a secure link (CU-COM-16). The response is a
 * `Cotizacion` linked to the availability request; the predictive module
 * evaluates it afterwards (CU-COM-20).
 */
class GestionDisponibilidadService
{
    public function __construct(
        private readonly AuditoriaService $auditoria,
        private readonly UsuariosService $usuarios,
        private readonly GestionArticulosService $articulos,
        private readonly ResolverDisponibilidadService $predictivo,
        private readonly PreciosDeReferenciaQuery $precios,
        private readonly ParametroGlobalService $parametros,
        private readonly AlertaService $alertas,
        private readonly GestionRankingService $ranking,
    ) {}

    public function puedeGestionar(Usuario $actor): bool
    {
        return $this->usuarios->autorizar($actor, 'compras.gestionar');
    }

    /**
     * @return array<int, array{id:int, articulo_id:int, articulo:string, proveedor_id:int, proveedor:string, cantidad_solicitada:float, fecha:string, fecha_requerida:string|null, estado:string, respondida:bool}>
     */
    public function listar(): array
    {
        $solicitudes = SolicitudDeDisponibilidad::query()
            ->with('proveedor')
            ->withExists(['cotizaciones as respondida'])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        $resumenes = $this->articulos->resumenesDeArticulos(
            $solicitudes->pluck('articulo_id')->unique()->values()->all(),
        );

        return $solicitudes
            ->map(fn (SolicitudDeDisponibilidad $solicitud): array => [
                'id' => (int) $solicitud->id,
                'articulo_id' => (int) $solicitud->articulo_id,
                'articulo' => $resumenes[$solicitud->articulo_id]['descripcion'] ?? 'Artículo #'.$solicitud->articulo_id,
                'proveedor_id' => (int) $solicitud->proveedor_id,
                'proveedor' => $solicitud->proveedor->razon_social,
                'cantidad_solicitada' => (float) $solicitud->cantidad_solicitada,
                'fecha' => $solicitud->fecha->toDateString(),
                'fecha_requerida' => $solicitud->fecha_requerida?->toDateString(),
                'estado' => (string) $solicitud->estado,
                'respondida' => (bool) $solicitud->respondida,
            ])
            ->values()
            ->all();
    }

    public function crear(
        Usuario $actor,
        int $articuloId,
        float $cantidad,
        int $proveedorId,
        ?string $fechaRequerida = null,
        ?string $direccionIp = null,
    ): SolicitudDeDisponibilidad {
        if ($cantidad <= 0) {
            throw ValidationException::withMessages([
                'cantidad' => 'La cantidad debe ser mayor a 0.',
            ]);
        }

        $proveedor = Proveedor::query()->find($proveedorId);

        if ($proveedor === null || $proveedor->estado !== 'Activo') {
            throw ValidationException::withMessages([
                'proveedor_id' => 'El proveedor no existe o no está activo.',
            ]);
        }

        if ($proveedor->email === null || trim((string) $proveedor->email) === '') {
            throw ValidationException::withMessages([
                'proveedor_id' => 'El proveedor no tiene email registrado: no se puede enviar la consulta.',
            ]);
        }

        if ($this->articulos->resumenesDeArticulos([$articuloId]) === []) {
            throw ValidationException::withMessages([
                'articulo_id' => 'El artículo no existe.',
            ]);
        }

        [$solicitud, $token] = DB::transaction(function () use ($actor, $articuloId, $cantidad, $proveedorId, $fechaRequerida, $direccionIp): array {
            $solicitud = SolicitudDeDisponibilidad::query()->create([
                'proveedor_id' => $proveedorId,
                'articulo_id' => $articuloId,
                'fecha' => today()->toDateString(),
                'fecha_requerida' => $fechaRequerida,
                'cantidad_solicitada' => round($cantidad, 2),
                'estado' => EstadoSolicitudDeDisponibilidad::Enviada->value,
                'token' => (string) Str::uuid(),
            ]);

            $this->auditoria->registrarEvento(
                $actor->id,
                'solicitar_disponibilidad',
                null,
                [
                    'articulo_id' => $articuloId,
                    'cantidad' => $cantidad,
                    'proveedor_id' => $proveedorId,
                    'fecha_requerida' => $fechaRequerida,
                ],
                $direccionIp,
                SolicitudDeDisponibilidad::class,
                $solicitud->id,
            );

            return [$solicitud, (string) $solicitud->token];
        });

        $this->enviarInvitacion($solicitud, $token);

        return $solicitud;
    }

    /**
     * @return array{solicitud_id:int, proveedor:string, articulo:string, cantidad_solicitada:float, fecha_requerida:string|null}|null
     */
    public function obtenerPorToken(string $token): ?array
    {
        $solicitud = SolicitudDeDisponibilidad::query()
            ->with('proveedor')
            ->where('token', $token)
            ->first();

        if ($solicitud === null || $solicitud->estado !== EstadoSolicitudDeDisponibilidad::Enviada->value) {
            return null;
        }

        $resumen = $this->articulos->resumenesDeArticulos([$solicitud->articulo_id])[$solicitud->articulo_id] ?? null;

        return [
            'solicitud_id' => (int) $solicitud->id,
            'proveedor' => $solicitud->proveedor->razon_social,
            'articulo' => $resumen['descripcion'] ?? 'Artículo #'.$solicitud->articulo_id,
            'cantidad_solicitada' => (float) $solicitud->cantidad_solicitada,
            'fecha_requerida' => $solicitud->fecha_requerida?->toDateString(),
        ];
    }

    public function registrarRespuesta(
        string $token,
        float $cantidadDisponible,
        float $precioUnitario,
        string $fechaPrometida,
        ?string $direccionIp = null,
    ): Cotizacion {
        return DB::transaction(function () use ($token, $cantidadDisponible, $precioUnitario, $fechaPrometida, $direccionIp): Cotizacion {
            $solicitud = SolicitudDeDisponibilidad::query()
                ->where('token', $token)
                ->lockForUpdate()
                ->first();

            if ($solicitud === null || $solicitud->estado !== EstadoSolicitudDeDisponibilidad::Enviada->value) {
                throw (new ModelNotFoundException)->setModel(SolicitudDeDisponibilidad::class);
            }

            $cotizacion = Cotizacion::query()->create([
                'solicitud_de_cotizacion_id' => null,
                'solicitud_de_disponibilidad_id' => $solicitud->id,
                'proveedor_id' => $solicitud->proveedor_id,
                'fecha' => today()->toDateString(),
            ]);

            $cotizacion->lineas()->create([
                'articulo_id' => $solicitud->articulo_id,
                'precio_unitario' => round($precioUnitario, 2),
                'cantidad_ofertada' => round($cantidadDisponible, 2),
                'fecha_prometida' => $fechaPrometida,
                'adjudicada' => false,
            ]);

            // Close the token: a supplier cannot answer twice.
            $solicitud->update(['token' => null]);

            $this->auditoria->registrarEvento(
                $this->usuarios->usuarioSistema()->id,
                'registrar_disponibilidad',
                null,
                [
                    'solicitud_de_disponibilidad_id' => $solicitud->id,
                    'cantidad_disponible' => $cantidadDisponible,
                    'precio_unitario' => $precioUnitario,
                    'fecha_prometida' => $fechaPrometida,
                ],
                $direccionIp,
                Cotizacion::class,
                $cotizacion->id,
            );

            return $cotizacion;
        });
    }

    /**
     * CU-COM-20: evaluate the supplier answer and apply the consequences.
     *
     * @return array{escenario:string, motivos:array<int,string>, desviacion_precio:float|null, dias_de_atraso:int|null, recomendacion:string, proveedor_sugerido:array{id:int, razon_social:string, puntaje:float}|null}
     */
    public function resolver(
        SolicitudDeDisponibilidad $solicitud,
        ?int $actorId = null,
        ?string $direccionIp = null,
    ): array {
        $resultado = $this->evaluar($solicitud);

        $this->marcarRespondida($solicitud, $actorId, $direccionIp);

        $proveedorSugerido = $resultado['escenario'] === 'Sin Stock'
            ? $this->siguienteProveedorPara((int) $solicitud->proveedor_id)
            : null;

        if ($resultado['escenario'] === 'Variación Fuerte') {
            $resumen = $this->articulos->resumenesDeArticulos([$solicitud->articulo_id])[$solicitud->articulo_id] ?? null;

            $this->alertas->emitirPorTipo('variacion_fuerte_disponibilidad', [
                'articulo' => $resumen['descripcion'] ?? 'Artículo #'.$solicitud->articulo_id,
                'motivos' => $this->motivosLegibles($resultado['motivos']),
                'precio_anterior' => $resultado['precio_referencia'] ?? '—',
                'precio_actual' => $resultado['precio_actual'],
                'fecha_prometida' => $resultado['fecha_prometida'],
                'fecha_requerida' => $solicitud->fecha_requerida?->toDateString() ?? '—',
                'articulo_id' => (int) $solicitud->articulo_id,
            ], 'disponibilidad:'.$solicitud->id);
        }

        return [...$resultado, 'proveedor_sugerido' => $proveedorSugerido];
    }

    /**
     * Read-only evaluation for display: no state changes, no alerts.
     *
     * @return array{escenario:string, motivos:array<int,string>, desviacion_precio:float|null, dias_de_atraso:int|null, recomendacion:string, proveedor_sugerido:array{id:int, razon_social:string, puntaje:float}|null}|null
     */
    public function recomendar(SolicitudDeDisponibilidad $solicitud): ?array
    {
        if (! $solicitud->cotizaciones()->exists()) {
            return null;
        }

        $resultado = $this->evaluar($solicitud);

        $proveedorSugerido = $resultado['escenario'] === 'Sin Stock'
            ? $this->siguienteProveedorPara((int) $solicitud->proveedor_id)
            : null;

        return [...$resultado, 'proveedor_sugerido' => $proveedorSugerido];
    }

    /**
     * @return array{escenario:string, motivos:array<int,string>, desviacion_precio:float|null, dias_de_atraso:int|null, recomendacion:string, precio_referencia:float|null, precio_actual:float, fecha_prometida:string}
     */
    private function evaluar(SolicitudDeDisponibilidad $solicitud): array
    {
        $respuesta = $solicitud->cotizaciones()->with('lineas')->latest('id')->first();

        if ($respuesta === null || $respuesta->lineas->isEmpty()) {
            throw ValidationException::withMessages([
                'respuesta' => 'La consulta todavía no tiene una respuesta del proveedor.',
            ]);
        }

        $linea = $respuesta->lineas->first();

        $referencia = $this->precios->ultimoPrecio(
            (int) $solicitud->articulo_id,
            (int) $solicitud->proveedor_id,
            (int) $respuesta->id,
        ) ?? $this->precios->ultimoPrecioDeCualquierProveedor((int) $solicitud->articulo_id);

        $umbralConfigurado = (float) ($this->parametros->parametro('compras_disponibilidad_umbral') ?? 0.15);
        $umbral = $umbralConfigurado > 0 ? $umbralConfigurado : 0.15;

        $resultado = $this->predictivo->resolver([
            'cantidad_disponible' => (float) $linea->cantidad_ofertada,
            'precio_unitario' => (float) $linea->precio_unitario,
            'precio_referencia' => $referencia,
            'umbral' => $umbral,
            'fecha_prometida' => $linea->fecha_prometida->toDateString(),
            'fecha_requerida' => $solicitud->fecha_requerida?->toDateString(),
        ]);

        return [
            ...$resultado,
            'precio_referencia' => $referencia,
            'precio_actual' => (float) $linea->precio_unitario,
            'fecha_prometida' => $linea->fecha_prometida->toDateString(),
        ];
    }

    /**
     * @param  array<int, string>  $motivos
     */
    private function motivosLegibles(array $motivos): string
    {
        $partes = [];

        if (in_array('precio_sobre_umbral', $motivos, true)) {
            $partes[] = 'el precio supera el umbral';
        }

        if (in_array('fecha_fuera_del_cronograma', $motivos, true)) {
            $partes[] = 'la fecha no llega al cronograma';
        }

        return $partes === [] ? 'sin motivo informado' : implode(' y ', $partes);
    }

    /**
     * @return array{id:int, razon_social:string, puntaje:float}|null
     */
    public function proveedorSugerido(): ?array
    {
        return $this->siguienteProveedorPara(null);
    }

    /**
     * @return array{id:int, razon_social:string, puntaje:float}|null
     */
    private function siguienteProveedorPara(?int $excluirProveedorId): ?array
    {
        foreach ($this->ranking->listar()['filas'] as $fila) {
            if ($excluirProveedorId !== null && $fila['proveedor_id'] === $excluirProveedorId) {
                continue;
            }

            $proveedor = Proveedor::query()->find($fila['proveedor_id']);

            if ($proveedor === null || $proveedor->email === null || trim((string) $proveedor->email) === '') {
                continue;
            }

            return [
                'id' => (int) $fila['proveedor_id'],
                'razon_social' => (string) $fila['proveedor'],
                'puntaje' => (float) $fila['puntaje'],
            ];
        }

        return null;
    }

    /**
     * @return array<int, array{id:int, razon_social:string}>
     */
    public function proveedoresConEmail(): array
    {
        return Proveedor::query()
            ->activos()
            ->whereNotNull('email')
            ->get(['id', 'razon_social'])
            ->map(fn (Proveedor $proveedor): array => [
                'id' => (int) $proveedor->id,
                'razon_social' => (string) $proveedor->razon_social,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listarConRecomendacion(): array
    {
        $solicitudes = SolicitudDeDisponibilidad::query()
            ->with('proveedor')
            ->withExists(['cotizaciones as respondida'])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        $resumenes = $this->articulos->resumenesDeArticulos(
            $solicitudes->pluck('articulo_id')->unique()->values()->all(),
        );

        return $solicitudes
            ->map(function (SolicitudDeDisponibilidad $solicitud) use ($resumenes): array {
                return [
                    'id' => (int) $solicitud->id,
                    'articulo_id' => (int) $solicitud->articulo_id,
                    'articulo' => $resumenes[$solicitud->articulo_id]['descripcion'] ?? 'Artículo #'.$solicitud->articulo_id,
                    'proveedor_id' => (int) $solicitud->proveedor_id,
                    'proveedor' => $solicitud->proveedor->razon_social,
                    'cantidad_solicitada' => (float) $solicitud->cantidad_solicitada,
                    'fecha' => $solicitud->fecha->toDateString(),
                    'fecha_requerida' => $solicitud->fecha_requerida?->toDateString(),
                    'estado' => (string) $solicitud->estado,
                    'respondida' => (bool) $solicitud->respondida,
                    'recomendacion' => $solicitud->respondida ? $this->recomendar($solicitud) : null,
                ];
            })
            ->values()
            ->all();
    }

    public function marcarRespondida(
        SolicitudDeDisponibilidad $solicitud,
        ?int $actorId = null,
        ?string $direccionIp = null,
    ): void {
        if ($solicitud->estado === EstadoSolicitudDeDisponibilidad::Respondida->value) {
            return;
        }

        $solicitud->update(['estado' => EstadoSolicitudDeDisponibilidad::Respondida->value]);

        $this->auditoria->registrarEvento(
            $actorId ?? $this->usuarios->usuarioSistema()->id,
            'resolver_disponibilidad',
            ['estado' => EstadoSolicitudDeDisponibilidad::Enviada->value],
            ['estado' => EstadoSolicitudDeDisponibilidad::Respondida->value],
            $direccionIp,
            SolicitudDeDisponibilidad::class,
            $solicitud->id,
        );
    }

    private function enviarInvitacion(SolicitudDeDisponibilidad $solicitud, string $token): void
    {
        $solicitud->loadMissing('proveedor');

        $resumen = $this->articulos->resumenesDeArticulos([$solicitud->articulo_id])[$solicitud->articulo_id] ?? null;

        try {
            Mail::to($solicitud->proveedor->email)->send(new SolicitudDeDisponibilidadMail(
                $solicitud,
                $solicitud->proveedor,
                $token,
                $resumen['descripcion'] ?? 'Artículo #'.$solicitud->articulo_id,
            ));
        } catch (\Throwable $excepcion) {
            Log::error('No se pudo enviar la consulta de disponibilidad.', [
                'solicitud_de_disponibilidad_id' => $solicitud->id,
                'error' => $excepcion->getMessage(),
            ]);
        }
    }
}
