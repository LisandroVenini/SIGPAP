<?php

namespace App\Modules\Produccion\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Inventario\Services\GestionArticulosService;
use App\Modules\Inventario\Services\GestionReservasService;
use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use App\Modules\Produccion\Enums\ModoDeAltaOrden;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Queries\OrdenesDeProduccionQuery;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\UsuariosService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionOrdenesDeProduccionService
{
    public function __construct(
        private readonly AuditoriaService $auditoria,
        private readonly GestionArticulosService $articulos,
        private readonly GestionRecetasService $recetas,
        private readonly GestionReservasService $reservas,
        private readonly OrdenesDeProduccionQuery $query,
        private readonly UsuariosService $usuarios,
    ) {}

    public function puedeGestionar(Usuario $actor): bool
    {
        return $this->usuarios->autorizar($actor, 'produccion.gestionar');
    }

    /**
     * @param  array{buscar?: string|null, estado?: string|null, fecha_desde?: string|null, fecha_hasta?: string|null}  $filtros
     */
    public function listar(array $filtros = []): LengthAwarePaginator
    {
        $filtrosQuery = $filtros;

        if (! empty($filtros['buscar'])) {
            $filtrosQuery['articulo_ids'] = $this->articulos->idsQueCoinciden($filtros['buscar']);
        }

        $resultados = $this->query->listar($filtrosQuery);

        $articuloIds = $resultados->pluck('articulo_id')->unique()->filter()->all();
        $resumenes = $this->articulos->resumenesDeArticulos($articuloIds);

        return $resultados->through(fn (OrdenDeProduccion $orden): array => [
            'id' => $orden->id,
            'producto' => $resumenes[$orden->articulo_id] ?? null,
            'planificacion' => $orden->planificacionDeProduccion?->nombre,
            'estado' => $orden->estado,
            'cantidad_requerida' => (float) $orden->cantidad_requerida,
            'cantidad_producida' => (float) $orden->cantidad_producida,
            'fecha_entrega' => $orden->fecha_entrega?->toDateString(),
            'prioridad_actual' => $orden->prioridad_actual,
        ]);
    }

    /**
     * @return array<int, float>
     */
    public function necesidades(int $articuloId, float $cantidad): array
    {
        $detalle = $this->recetas->detalle($articuloId);
        $componentes = $detalle['componentes'] ?? [];

        if ($componentes === []) {
            throw ValidationException::withMessages([
                'articulo' => 'El producto no tiene receta o está vacía.',
            ]);
        }

        $necesidades = [];

        foreach ($componentes as $componente) {
            $necesidades[$componente['articulo_id']] = round($cantidad * $componente['cantidad'], 2);
        }

        return $necesidades;
    }

    /**
     * @param  array<int, array{articulo_id: int, cantidad: float}>  $lineas
     * @return array{creadas: int, pendientes: int, omitidas: int}
     */
    public function crear(
        Usuario $actor,
        int $planificacionId,
        string $fechaEntrega,
        array $lineas,
        ModoDeAltaOrden $modo,
        ?string $direccionIp = null,
    ): array {
        return DB::transaction(function () use ($actor, $planificacionId, $fechaEntrega, $lineas, $modo, $direccionIp): array {
            $creadas = 0;
            $pendientes = 0;
            $omitidas = 0;

            foreach ($lineas as $linea) {
                $articuloId = (int) $linea['articulo_id'];
                $cantidad = (float) $linea['cantidad'];

                $necesidades = $this->necesidades($articuloId, $cantidad);

                $orden = OrdenDeProduccion::create([
                    'articulo_id' => $articuloId,
                    'planificacion_de_produccion_id' => $planificacionId,
                    'estado' => EstadoOrdenProduccion::PendientePorInsumos->value,
                    'cantidad_requerida' => $cantidad,
                    'fecha_entrega' => $fechaEntrega,
                    'cantidad_producida' => 0,
                    'prioridad_actual' => 1,
                ]);

                $reserva = $this->reservas->reservar($orden->id, $necesidades);

                if ($reserva['cubierto']) {
                    $orden->estado = EstadoOrdenProduccion::Pendiente->value;
                    $orden->save();
                    $creadas++;
                } elseif ($modo === ModoDeAltaOrden::SoloDisponibles) {
                    // The reservation is all-or-nothing, so nothing is reserved when not covered.
                    // This release is a defensive safeguard in case that invariant ever changes.
                    $this->reservas->liberar($orden->id);
                    $orden->delete();
                    $omitidas++;

                    continue;
                } else {
                    $pendientes++;
                }

                $this->auditoria->registrarEvento(
                    $actor->id,
                    'crear_orden_produccion',
                    null,
                    [
                        'articulo_id' => $orden->articulo_id,
                        'cantidad' => $orden->cantidad_requerida,
                        'estado' => $orden->estado,
                    ],
                    $direccionIp,
                    OrdenDeProduccion::class,
                    $orden->id,
                );
            }

            return [
                'creadas' => $creadas,
                'pendientes' => $pendientes,
                'omitidas' => $omitidas,
            ];
        });
    }

    public function anular(
        Usuario $actor,
        OrdenDeProduccion $orden,
        ?string $motivo = null,
        ?string $direccionIp = null,
    ): void {
        DB::transaction(function () use ($actor, $orden, $motivo, $direccionIp): void {
            $orden = OrdenDeProduccion::query()->whereKey($orden->getKey())->lockForUpdate()->firstOrFail();

            $estadosPermitidos = [
                EstadoOrdenProduccion::Pendiente->value,
                EstadoOrdenProduccion::PendientePorInsumos->value,
                EstadoOrdenProduccion::Asignada->value,
            ];

            if (! in_array($orden->estado, $estadosPermitidos, true)) {
                throw ValidationException::withMessages([
                    'estado' => 'La orden no puede anularse en el estado actual.',
                ]);
            }

            $this->reservas->liberar($orden->id);

            $anterior = ['estado' => $orden->estado];
            $orden->estado = EstadoOrdenProduccion::Anulada->value;
            $orden->save();

            $this->auditoria->registrarEvento(
                $actor->id,
                'anular_orden_produccion',
                $anterior,
                ['estado' => $orden->estado, 'motivo' => $motivo],
                $direccionIp,
                OrdenDeProduccion::class,
                $orden->id,
            );
        });
    }

    public function modificar(
        Usuario $actor,
        OrdenDeProduccion $orden,
        float $cantidad,
        string $fechaEntrega,
        ?string $direccionIp = null,
    ): void {
        DB::transaction(function () use ($actor, $orden, $cantidad, $fechaEntrega, $direccionIp): void {
            $orden = OrdenDeProduccion::query()->whereKey($orden->getKey())->lockForUpdate()->firstOrFail();

            $estadosPermitidos = [
                EstadoOrdenProduccion::Pendiente->value,
                EstadoOrdenProduccion::PendientePorInsumos->value,
            ];

            if (! in_array($orden->estado, $estadosPermitidos, true)) {
                throw ValidationException::withMessages([
                    'estado' => 'La orden no puede modificarse en el estado actual.',
                ]);
            }

            $necesidades = $this->necesidades($orden->articulo_id, $cantidad);
            $reserva = $this->reservas->reemplazar($orden->id, $necesidades);

            $nuevoEstado = $reserva['cubierto']
                ? EstadoOrdenProduccion::Pendiente->value
                : EstadoOrdenProduccion::PendientePorInsumos->value;

            $anterior = [
                'cantidad_requerida' => (float) $orden->cantidad_requerida,
                'fecha_entrega' => $orden->fecha_entrega?->toDateString(),
                'estado' => $orden->estado,
            ];

            $orden->cantidad_requerida = $cantidad;
            $orden->fecha_entrega = $fechaEntrega;
            $orden->estado = $nuevoEstado;
            $orden->save();

            $this->auditoria->registrarEvento(
                $actor->id,
                'modificar_orden_produccion',
                $anterior,
                [
                    'cantidad_requerida' => $cantidad,
                    'fecha_entrega' => $fechaEntrega,
                    'estado' => $nuevoEstado,
                ],
                $direccionIp,
                OrdenDeProduccion::class,
                $orden->id,
            );
        });
    }
}
