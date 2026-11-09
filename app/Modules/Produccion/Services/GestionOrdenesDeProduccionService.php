<?php

namespace App\Modules\Produccion\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Inventario\Services\GestionArticulosService;
use App\Modules\Inventario\Services\GestionReservasService;
use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use App\Modules\Produccion\Enums\ModoDeAltaOrden;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Queries\CalidadQuery;
use App\Modules\Produccion\Queries\DashboardProduccionQuery;
use App\Modules\Produccion\Queries\HojaDeRutaQuery;
use App\Modules\Produccion\Queries\OrdenesDeProduccionQuery;
use App\Modules\Produccion\Queries\PartesDiariosQuery;
use App\Modules\Produccion\Queries\ReporteProduccionQuery;
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
        private readonly CalidadQuery $calidad,
        private readonly DashboardProduccionQuery $dashboard,
        private readonly HojaDeRutaQuery $hojaDeRuta,
        private readonly OrdenesDeProduccionQuery $query,
        private readonly PartesDiariosQuery $partesDiarios,
        private readonly ReporteProduccionQuery $reporteProduccion,
        private readonly UsuariosService $usuarios,
    ) {}

    public function puedeGestionar(Usuario $actor): bool
    {
        return $this->usuarios->autorizar($actor, 'produccion.gestionar');
    }

    /**
     * Production indicators and the next deliveries for the dashboard.
     *
     * @return array{activas: int, en_curso: int, pendientes_por_insumos: int, atrasadas: int, proximas_entregas: array<int, array{orden_id: int, producto: string, fecha_entrega: string, estado: string}>}
     */
    public function resumenDashboard(): array
    {
        $hoy = today()->toDateString();
        $proximas = $this->dashboard->proximasEntregas(5, $hoy);

        $resumenes = $this->articulos->resumenesDeArticulos(
            $proximas->pluck('articulo_id')->unique()->values()->all(),
        );

        return [
            ...$this->dashboard->agregados($hoy),
            'proximas_entregas' => $proximas
                ->map(fn (array $orden): array => [
                    'orden_id' => $orden['orden_id'],
                    'producto' => $resumenes[$orden['articulo_id']]['descripcion'] ?? '',
                    'fecha_entrega' => $orden['fecha_entrega'],
                    'estado' => $orden['estado'],
                ])
                ->all(),
        ];
    }

    /**
     * Production orders inside the delivery date range, already formatted for reports.
     *
     * @return array<int, array<string, mixed>>
     */
    public function reportePorFechaDeEntrega(string $desde, string $hasta, ?string $estado): array
    {
        $filas = $this->reporteProduccion->filas($desde, $hasta, $estado)->values();

        $resumenes = $this->articulos->resumenesDeArticulos(
            $filas->pluck('articulo_id')->unique()->values()->all(),
        );

        return $filas
            ->map(fn (array $fila): array => [
                'orden_id' => $fila['orden_id'],
                'producto' => $resumenes[$fila['articulo_id']]['descripcion'] ?? '',
                'planificacion' => $fila['planificacion'],
                'estado' => $fila['estado'],
                'cantidad_requerida' => $fila['cantidad_requerida'],
                'cantidad_producida' => $fila['cantidad_producida'],
                'fecha_entrega' => $fila['fecha_entrega'],
                'fecha_inicio' => $fila['fecha_inicio'],
                'fecha_fin' => $fila['fecha_fin'],
            ])
            ->all();
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
        $etapasPorArticulo = $this->hojaDeRuta->etapasPorArticulo($articuloIds);
        $etapasConIdPorArticulo = $this->hojaDeRuta->etapasConIdPorArticulo($articuloIds);
        $primerasEtapas = $this->hojaDeRuta->primeraEtapaPorArticulo($articuloIds);
        $ultimasEtapas = $this->hojaDeRuta->ultimaEtapaPorArticulo($articuloIds);

        $ordenIds = $resultados->pluck('id')->unique()->all();
        $partesPorOrden = $this->partesDiarios->porOrdenes($ordenIds);
        $conformadoPorOrden = $this->partesDiarios->conformadoPorOrdenes($ordenIds);
        $aprobadasParaCierre = $this->calidad->aprobadoParaCierre($ordenIds, $ultimasEtapas);

        return $resultados->through(function (OrdenDeProduccion $orden) use (
            $resumenes,
            $etapasPorArticulo,
            $etapasConIdPorArticulo,
            $primerasEtapas,
            $ultimasEtapas,
            $partesPorOrden,
            $conformadoPorOrden,
            $aprobadasParaCierre,
        ): array {
            $partes = $partesPorOrden[$orden->id] ?? collect();
            $conformadoPorEtapa = $conformadoPorOrden[$orden->id] ?? [];
            $primeraEtapaId = $primerasEtapas[$orden->articulo_id] ?? null;
            $ultimaEtapaId = $ultimasEtapas[$orden->articulo_id] ?? null;
            $conformadoPrimeraEtapa = $primeraEtapaId !== null
                ? ($conformadoPorEtapa[$primeraEtapaId] ?? 0.0)
                : 0.0;
            $conformadoUltimaEtapa = $ultimaEtapaId !== null
                ? ($conformadoPorEtapa[$ultimaEtapaId] ?? 0.0)
                : 0.0;

            // A unit only counts as produced once it passed every etapa, so the
            // progress is the minimum conformed across the product's route.
            $etapasDeLaRuta = $etapasConIdPorArticulo[$orden->articulo_id] ?? [];
            $conformadoCompleto = $etapasDeLaRuta === []
                ? 0.0
                : (float) min(array_map(
                    fn (array $etapa): float => $conformadoPorEtapa[$etapa['id']] ?? 0.0,
                    $etapasDeLaRuta,
                ));

            $requerida = (float) $orden->cantidad_requerida;
            $avance = $requerida > 0
                ? round(min(100.0, ($conformadoCompleto / $requerida) * 100), 2)
                : 0.0;

            $resumenCalidad = $aprobadasParaCierre[$orden->id] ?? ['aprobado' => null, 'tiene_control' => false];

            return [
                'id' => $orden->id,
                'producto' => $resumenes[$orden->articulo_id] ?? null,
                'planificacion' => $orden->planificacionDeProduccion?->nombre,
                'estado' => $orden->estado,
                'cantidad_requerida' => $requerida,
                'cantidad_producida' => (float) $orden->cantidad_producida,
                'fecha_entrega' => $orden->fecha_entrega?->toDateString(),
                'fecha_inicio' => $orden->fecha_inicio?->toDateString(),
                'fecha_prevista_inicio' => $orden->fecha_prevista_inicio?->toDateString(),
                'prioridad_actual' => $orden->prioridad_actual,
                'operarios' => $orden->operarios
                    ->sortBy(['apellido', 'nombre'])
                    ->map(fn ($operario): string => "{$operario->apellido}, {$operario->nombre}")
                    ->values()
                    ->all(),
                'operario_ids' => $orden->operarios->pluck('id')->values()->all(),
                'maquinarias' => $orden->maquinarias->pluck('nombre')->values()->all(),
                'maquinaria_ids' => $orden->maquinarias->pluck('id')->values()->all(),
                'etapas' => $etapasPorArticulo[$orden->articulo_id] ?? [],
                'etapas_con_ids' => $etapasConIdPorArticulo[$orden->articulo_id] ?? [],
                'partes' => $partes->all(),
                'conformado_primera_etapa' => $conformadoPrimeraEtapa,
                'conformado_ultima_etapa' => $conformadoUltimaEtapa,
                'aprobadas_ultima_etapa' => $resumenCalidad['aprobado'],
                'tiene_control_calidad' => $resumenCalidad['tiene_control'],
                'avance' => $avance,
            ];
        });
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

            if ($orden->fecha_inicio !== null) {
                throw ValidationException::withMessages([
                    'estado' => 'La orden ya fue iniciada y no puede anularse.',
                ]);
            }

            if ($orden->partesDiarios()->exists()) {
                throw ValidationException::withMessages([
                    'estado' => 'La orden tiene partes diarios registrados y no puede anularse.',
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
