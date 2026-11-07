<?php

namespace App\Modules\Compras\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Compras\Models\Cotizacion;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Models\SolicitudDeCotizacion;
use App\Modules\Inventario\Services\GestionArticulosService;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionCotizacionesService
{
    public function __construct(
        private readonly AuditoriaService $auditoria,
        private readonly GestionArticulosService $articulos,
    ) {}

    /**
     * @return array{solicitud_id: int, proveedor: string, fecha_vencimiento: string, lineas: array<int, array{articulo_id: int, articulo: string, cantidad_solicitada: float}>}|null
     */
    public function obtenerPorToken(string $token): ?array
    {
        $encontrado = $this->resolverToken($token);

        if ($encontrado === null || $this->estaVencida($encontrado['solicitud'])) {
            return null;
        }

        $solicitud = $encontrado['solicitud'];
        $resumenes = $this->articulos->resumenesDeArticulos(
            $solicitud->lineas->pluck('articulo_id')->all(),
        );

        return [
            'solicitud_id' => $solicitud->id,
            'proveedor' => $encontrado['proveedor']->razon_social,
            'fecha_vencimiento' => $solicitud->fecha_vencimiento->format('Y-m-d'),
            'lineas' => $solicitud->lineas->map(fn ($linea): array => [
                'articulo_id' => $linea->articulo_id,
                'articulo' => $resumenes[$linea->articulo_id]['descripcion'] ?? '',
                'cantidad_solicitada' => (float) $linea->cantidad_solicitada,
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<int, array{articulo_id: int, precio_unitario: float|int|string, cantidad_ofertada: float|int|string, fecha_prometida: string}>  $lineas
     */
    public function registrar(string $token, array $lineas, ?string $direccionIp = null, ?Usuario $actor = null): Cotizacion
    {
        return DB::transaction(function () use ($token, $lineas, $direccionIp, $actor): Cotizacion {
            $encontrado = $this->resolverToken($token, true);

            if ($encontrado === null) {
                throw (new ModelNotFoundException)->setModel(SolicitudDeCotizacion::class);
            }

            $solicitud = $encontrado['solicitud'];
            $proveedor = $encontrado['proveedor'];

            if ($this->estaVencida($solicitud)) {
                throw (new ModelNotFoundException)->setModel(SolicitudDeCotizacion::class);
            }

            $this->validarLineas($lineas);
            $this->validarArticulosDeLaSolicitud($solicitud, $lineas);

            $usuarioId = $this->usuarioDeAuditoria($solicitud, $actor);

            $cotizacion = Cotizacion::create([
                'solicitud_de_cotizacion_id' => $solicitud->id,
                'solicitud_de_disponibilidad_id' => null,
                'proveedor_id' => $proveedor->id,
                'fecha' => today()->toDateString(),
            ]);

            foreach ($lineas as $linea) {
                $cotizacion->lineas()->create([
                    'articulo_id' => $linea['articulo_id'],
                    'precio_unitario' => $linea['precio_unitario'],
                    'cantidad_ofertada' => $linea['cantidad_ofertada'],
                    'fecha_prometida' => $linea['fecha_prometida'],
                    'adjudicada' => false,
                ]);
            }

            $proveedor->solicitudesDeCotizacion()->updateExistingPivot($solicitud->id, ['token' => null]);

            $this->auditoria->registrarEvento(
                $usuarioId,
                'registrar_cotizacion',
                null,
                [
                    'solicitud_de_cotizacion_id' => $solicitud->id,
                    'proveedor_id' => $proveedor->id,
                    'lineas' => count($lineas),
                ],
                $direccionIp,
                Cotizacion::class,
                $cotizacion->id,
            );

            return $cotizacion;
        });
    }

    /**
     * @return array{solicitud: SolicitudDeCotizacion, proveedor: Proveedor}|null
     */
    private function resolverToken(string $token, bool $lock = false): ?array
    {
        $consulta = DB::table('solicitud_de_cotizacion_proveedor')->where('token', $token);

        if ($lock) {
            $consulta->lockForUpdate();
        }

        $pivot = $consulta->first();

        if ($pivot === null) {
            return null;
        }

        $solicitud = SolicitudDeCotizacion::query()
            ->with('lineas')
            ->find($pivot->solicitud_de_cotizacion_id);
        $proveedor = Proveedor::query()->find($pivot->proveedor_id);

        if ($solicitud === null || $proveedor === null) {
            return null;
        }

        return [
            'solicitud' => $solicitud,
            'proveedor' => $proveedor,
        ];
    }

    private function estaVencida(SolicitudDeCotizacion $solicitud): bool
    {
        return $solicitud->fecha_vencimiento->lt(today());
    }

    /**
     * The supplier must quote exactly the articles requested, with no additions or omissions.
     *
     * @param  array<int, array{articulo_id: int}>  $lineas
     */
    private function validarArticulosDeLaSolicitud(SolicitudDeCotizacion $solicitud, array $lineas): void
    {
        $esperados = $solicitud->lineas()->pluck('articulo_id')
            ->map(fn ($id): int => (int) $id)
            ->sort()
            ->values()
            ->all();

        $recibidos = collect($lineas)->pluck('articulo_id')
            ->map(fn ($id): int => (int) $id)
            ->sort()
            ->values()
            ->all();

        if ($esperados !== $recibidos) {
            throw ValidationException::withMessages([
                'lineas' => 'La cotización debe incluir exactamente los artículos solicitados.',
            ]);
        }
    }

    private function validarLineas(array $lineas): void
    {
        foreach ($lineas as $indice => $linea) {
            if ((float) ($linea['precio_unitario'] ?? 0) <= 0) {
                throw ValidationException::withMessages([
                    "lineas.{$indice}.precio_unitario" => 'El precio unitario debe ser mayor a 0.',
                ]);
            }

            if ((float) ($linea['cantidad_ofertada'] ?? 0) <= 0) {
                throw ValidationException::withMessages([
                    "lineas.{$indice}.cantidad_ofertada" => 'La cantidad ofertada debe ser mayor a 0.',
                ]);
            }

            $fechaPrometida = $linea['fecha_prometida'] ?? null;

            if ($fechaPrometida === null || Carbon::parse($fechaPrometida)->lt(today())) {
                throw ValidationException::withMessages([
                    "lineas.{$indice}.fecha_prometida" => 'La fecha prometida no puede ser anterior a hoy.',
                ]);
            }
        }
    }

    private function usuarioDeAuditoria(SolicitudDeCotizacion $solicitud, ?Usuario $actor): int
    {
        if ($actor !== null) {
            return $actor->id;
        }

        $usuarioId = $this->auditoria->usuarioResponsableDe(
            SolicitudDeCotizacion::class,
            $solicitud->id,
            ['crear_solicitud_cotizacion', 'actualizar_solicitud_cotizacion'],
        );

        if ($usuarioId === null) {
            throw (new ModelNotFoundException)->setModel(SolicitudDeCotizacion::class);
        }

        return $usuarioId;
    }
}
