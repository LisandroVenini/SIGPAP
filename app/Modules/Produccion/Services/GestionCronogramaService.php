<?php

namespace App\Modules\Produccion\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Inventario\Services\GestionArticulosService;
use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use App\Modules\Produccion\Enums\MotivoCambioPrioridad;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Models\RegistroPrioridad;
use App\Modules\Produccion\Queries\CronogramaQuery;
use App\Modules\Produccion\Queries\HojaDeRutaQuery;
use App\Modules\Produccion\Queries\PartesDiariosQuery;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\UsuariosService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionCronogramaService
{
    private const DIAS_MINIMOS = 14;

    public function __construct(
        private readonly AuditoriaService $auditoria,
        private readonly CalendarioLaboral $calendario,
        private readonly CronogramaQuery $query,
        private readonly GestionArticulosService $articulos,
        private readonly HojaDeRutaQuery $hojaDeRuta,
        private readonly PartesDiariosQuery $partesDiarios,
        private readonly UsuariosService $usuarios,
    ) {}

    /**
     * @return array{inicio: string, hoy: string, dias: int, filas: array<int, array{orden_id:int, producto:string, estado:string, prioridad:int, fecha_entrega:string|null, reubicable:bool, conflicto_entrega:bool, cantidad_requerida:float, etapa_actual:string|null, avance:float, dias_totales:int, etapas: array<int, array{etapa_id:int, etapa:string, inicio:string, fin:string, inicio_en:string, fin_en:string, dias:int, minutos:int, segmentos: array<int, array{inicio:string, fin:string}>, producida:float}>, historial: array<int, array{prioridad_anterior:int|null, prioridad_nueva:int, motivo:string, detalle:string|null, fecha:string|null, usuario:string|null}>}>}
     */
    public function proyectar(): array
    {
        $inicio = Carbon::today();
        $filas = $this->construirFilas($this->query->ordenesActivas());

        $ultimoFin = collect($filas)
            ->pluck('etapas')
            ->flatten(1)
            ->pluck('fin_en')
            ->filter()
            ->max();

        $dias = $ultimoFin !== null
            ? (int) $inicio->diffInDays(Carbon::parse($ultimoFin)->startOfDay()) + 1
            : 0;

        return [
            'inicio' => $inicio->toDateString(),
            'hoy' => $inicio->toDateString(),
            'dias' => max(self::DIAS_MINIMOS, $dias),
            'filas' => $filas,
        ];
    }

    public function reordenar(
        Usuario $actor,
        OrdenDeProduccion $orden,
        int $nuevaPrioridad,
        string $motivo,
        ?string $detalle,
        bool $confirmarConflicto,
        ?string $direccionIp = null,
    ): void {
        if ($nuevaPrioridad < 1) {
            throw ValidationException::withMessages([
                'prioridad' => 'La prioridad debe ser un número mayor o igual a 1.',
            ]);
        }

        if (! in_array($motivo, MotivoCambioPrioridad::values(), true)) {
            throw ValidationException::withMessages([
                'motivo' => 'El motivo del cambio de prioridad no es válido.',
            ]);
        }

        DB::transaction(function () use ($actor, $orden, $nuevaPrioridad, $motivo, $detalle, $confirmarConflicto, $direccionIp): void {
            $orden = OrdenDeProduccion::query()->whereKey($orden->getKey())->lockForUpdate()->firstOrFail();

            if ($orden->estado === EstadoOrdenProduccion::EnCurso->value) {
                throw ValidationException::withMessages([
                    'estado' => 'Las órdenes En Curso no pueden reubicarse.',
                ]);
            }

            $conflictosPrevios = $this->conflictos($this->proyectar()['filas']);
            $prioridadAnterior = (int) $orden->prioridad_actual;

            $orden->prioridad_actual = $nuevaPrioridad;
            $orden->save();

            $conflictosNuevos = array_values(array_diff($this->conflictos($this->proyectar()['filas']), $conflictosPrevios));

            if ($conflictosNuevos !== [] && ! $confirmarConflicto) {
                throw ValidationException::withMessages([
                    'conflicto' => 'El reordenamiento incumple la fecha de entrega de las órdenes: '.implode(', ', $conflictosNuevos).'.',
                ]);
            }

            RegistroPrioridad::create([
                'orden_de_produccion_id' => $orden->id,
                'usuario_id' => $actor->id,
                'prioridad_anterior' => $prioridadAnterior,
                'prioridad_nueva' => $nuevaPrioridad,
                'fecha_cambio' => now(),
                'motivo' => $motivo,
                'detalle' => $detalle,
            ]);

            $this->auditoria->registrarEvento(
                $actor->id,
                'reordenar_orden_produccion',
                ['prioridad_actual' => $prioridadAnterior],
                ['prioridad_actual' => $nuevaPrioridad, 'motivo' => $motivo, 'detalle' => $detalle],
                $direccionIp,
                OrdenDeProduccion::class,
                $orden->id,
            );
        });
    }

    /**
     * @param  Collection<int, OrdenDeProduccion>  $ordenes
     * @return array<int, array{orden_id:int, producto:string, estado:string, prioridad:int, fecha_entrega:string|null, reubicable:bool, conflicto_entrega:bool, cantidad_requerida:float, etapa_actual:string|null, avance:float, dias_totales:int, etapas: array<int, array{etapa_id:int, etapa:string, inicio:string, fin:string, inicio_en:string, fin_en:string, dias:int, minutos:int, segmentos: array<int, array{inicio:string, fin:string}>, producida:float}>, historial: array<int, array{prioridad_anterior:int|null, prioridad_nueva:int, motivo:string, detalle:string|null, fecha:string|null, usuario:string|null}>}>
     */
    private function construirFilas(Collection $ordenes): array
    {
        $articuloIds = $ordenes->pluck('articulo_id')->unique()->values()->all();
        $resumenes = $this->articulos->resumenesDeArticulos($articuloIds);
        $rutas = $this->hojaDeRuta->rutaPorArticulo($articuloIds);
        $primerasEtapas = $this->hojaDeRuta->primeraEtapaPorArticulo($articuloIds);
        $conformado = $this->partesDiarios->conformadoPorOrdenes($ordenes->pluck('id')->all());

        $cursor = CarbonImmutable::instance(now());
        $filas = [];

        foreach ($ordenes as $orden) {
            $cantidad = (float) $orden->cantidad_requerida;
            $cantidadRequerida = (float) $orden->cantidad_requerida;
            $conformadoPorEtapa = $conformado[$orden->id] ?? [];
            $rutaDelProducto = $rutas[$orden->articulo_id] ?? [];

            if ($orden->estado === EstadoOrdenProduccion::EnCurso->value) {
                $primeraEtapaId = $primerasEtapas[$orden->articulo_id] ?? null;
                $conformadoPrimera = $primeraEtapaId !== null
                    ? ($conformado[$orden->id][$primeraEtapaId] ?? 0.0)
                    : 0.0;
                $cantidad = max(0.0, round($cantidad - $conformadoPrimera, 2));
            }

            $inicio = $cursor;

            if ($orden->estado === EstadoOrdenProduccion::Pospuesta->value
                && $orden->fecha_prevista_inicio !== null
                && $orden->fecha_prevista_inicio->copy()->startOfDay()->gt($inicio)) {
                $inicio = CarbonImmutable::instance($orden->fecha_prevista_inicio)->startOfDay();
            }

            $segmentos = [];
            $ultimoFin = null;

            foreach ($rutaDelProducto as $etapa) {
                $minutos = $cantidad * $etapa['tiempo_por_unidad'];
                $bloques = $this->calendario->segmentos($inicio, $minutos);

                if ($bloques === []) {
                    // Zero workload: the stage still occupies an instant on the
                    // next working moment, so the Gantt can place it.
                    $instante = $this->calendario->siguienteInicio($inicio);
                    $bloques = [['inicio' => $instante, 'fin' => $instante]];
                }

                $primerInicio = $bloques[0]['inicio'];
                $finEtapa = $bloques[array_key_last($bloques)]['fin'];

                $segmentos[] = [
                    'etapa_id' => $etapa['etapa_id'],
                    'etapa' => $etapa['etapa'],
                    'inicio' => $primerInicio->toDateString(),
                    'fin' => $finEtapa->toDateString(),
                    'inicio_en' => $primerInicio->toDateTimeString(),
                    'fin_en' => $finEtapa->toDateTimeString(),
                    'dias' => max(1, (int) $primerInicio->startOfDay()->diffInDays($finEtapa->startOfDay()) + 1),
                    'minutos' => (int) ceil($minutos),
                    'segmentos' => array_map(fn (array $bloque): array => [
                        'inicio' => $bloque['inicio']->toDateTimeString(),
                        'fin' => $bloque['fin']->toDateTimeString(),
                    ], $bloques),
                    'producida' => round((float) ($conformadoPorEtapa[$etapa['etapa_id']] ?? 0.0), 2),
                ];

                $ultimoFin = $finEtapa;
                $inicio = $finEtapa;
            }

            if ($ultimoFin !== null) {
                $cursor = $ultimoFin;
            }

            $fechaEntrega = $orden->fecha_entrega?->toDateString();
            $conformadoCompleto = $rutaDelProducto === []
                ? 0.0
                : (float) min(array_map(
                    fn (array $etapa): float => (float) ($conformadoPorEtapa[$etapa['etapa_id']] ?? 0.0),
                    $rutaDelProducto,
                ));
            $avance = $cantidadRequerida > 0
                ? round(min(100.0, ($conformadoCompleto / $cantidadRequerida) * 100), 2)
                : 0.0;
            $etapaActual = null;

            foreach ($rutaDelProducto as $etapa) {
                if ((float) ($conformadoPorEtapa[$etapa['etapa_id']] ?? 0.0) < $cantidadRequerida) {
                    $etapaActual = $etapa['etapa'];

                    break;
                }
            }

            if ($etapaActual === null && $rutaDelProducto !== []) {
                $etapaActual = $rutaDelProducto[array_key_last($rutaDelProducto)]['etapa'];
            }

            $filas[] = [
                'orden_id' => $orden->id,
                'producto' => $resumenes[$orden->articulo_id]['descripcion'] ?? '',
                'estado' => $orden->estado,
                'prioridad' => (int) $orden->prioridad_actual,
                'fecha_entrega' => $fechaEntrega,
                'reubicable' => $orden->estado !== EstadoOrdenProduccion::EnCurso->value,
                'conflicto_entrega' => $fechaEntrega !== null
                    && $ultimoFin !== null
                    && $ultimoFin->startOfDay()->greaterThan(Carbon::parse($fechaEntrega)->startOfDay()),
                'cantidad_requerida' => $cantidadRequerida,
                'etapa_actual' => $etapaActual,
                'avance' => $avance,
                'dias_totales' => array_sum(array_column($segmentos, 'dias')),
                'etapas' => $segmentos,
            ];
        }

        $registros = RegistroPrioridad::query()
            ->whereIn('orden_de_produccion_id', $ordenes->pluck('id')->all())
            ->orderByDesc('fecha_cambio')
            ->orderByDesc('id')
            ->get();

        $usuarios = $this->usuarios->resumenesDeUsuarios(
            $registros->pluck('usuario_id')->unique()->values()->all(),
        );

        $historialPorOrden = $registros
            ->groupBy('orden_de_produccion_id')
            ->map(fn ($grupo) => $grupo->map(fn (RegistroPrioridad $registro): array => [
                'prioridad_anterior' => $registro->prioridad_anterior !== null ? (int) $registro->prioridad_anterior : null,
                'prioridad_nueva' => (int) $registro->prioridad_nueva,
                'motivo' => $registro->motivo,
                'detalle' => $registro->detalle,
                'fecha' => $registro->fecha_cambio?->toDateTimeString(),
                'usuario' => $usuarios[$registro->usuario_id]['nombre'] ?? null,
            ])->values()->all())
            ->all();

        foreach ($filas as $indice => $fila) {
            $filas[$indice]['historial'] = $historialPorOrden[$fila['orden_id']] ?? [];
        }

        return $filas;
    }

    /**
     * @param  array<int, array{orden_id:int, conflicto_entrega:bool}>  $filas
     * @return array<int, int>
     */
    private function conflictos(array $filas): array
    {
        return collect($filas)
            ->where('conflicto_entrega', true)
            ->pluck('orden_id')
            ->values()
            ->all();
    }
}
