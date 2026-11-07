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
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionCronogramaService
{
    private const HORAS_JORNADA = 8;

    private const DIAS_MINIMOS = 14;

    public function __construct(
        private readonly AuditoriaService $auditoria,
        private readonly CronogramaQuery $query,
        private readonly GestionArticulosService $articulos,
        private readonly HojaDeRutaQuery $hojaDeRuta,
        private readonly PartesDiariosQuery $partesDiarios,
    ) {}

    /**
     * @return array{inicio: string, dias: int, filas: array<int, array{orden_id:int, producto:string, estado:string, prioridad:int, fecha_entrega:string|null, reubicable:bool, conflicto_entrega:bool, etapas: array<int, array{etapa:string, inicio:string, fin:string, dias:int}>}>}
     */
    public function proyectar(): array
    {
        $inicio = Carbon::today();
        $filas = $this->construirFilas($this->query->ordenesActivas());

        $ultimoFin = collect($filas)
            ->pluck('etapas')
            ->flatten(1)
            ->pluck('fin')
            ->filter()
            ->max();

        $dias = $ultimoFin !== null
            ? (int) $inicio->diffInDays(Carbon::parse($ultimoFin)) + 1
            : 0;

        return [
            'inicio' => $inicio->toDateString(),
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
     * @return array<int, array{orden_id:int, producto:string, estado:string, prioridad:int, fecha_entrega:string|null, reubicable:bool, conflicto_entrega:bool, etapas: array<int, array{etapa:string, inicio:string, fin:string, dias:int}>}>
     */
    private function construirFilas(Collection $ordenes): array
    {
        $articuloIds = $ordenes->pluck('articulo_id')->unique()->values()->all();
        $resumenes = $this->articulos->resumenesDeArticulos($articuloIds);
        $rutas = $this->hojaDeRuta->rutaPorArticulo($articuloIds);
        $primerasEtapas = $this->hojaDeRuta->primeraEtapaPorArticulo($articuloIds);
        $conformado = $this->partesDiarios->conformadoPorOrdenes($ordenes->pluck('id')->all());

        $cursor = Carbon::today();
        $filas = [];

        foreach ($ordenes as $orden) {
            $cantidad = (float) $orden->cantidad_requerida;

            if ($orden->estado === EstadoOrdenProduccion::EnCurso->value) {
                $primeraEtapaId = $primerasEtapas[$orden->articulo_id] ?? null;
                $conformadoPrimera = $primeraEtapaId !== null
                    ? ($conformado[$orden->id][$primeraEtapaId] ?? 0.0)
                    : 0.0;
                $cantidad = max(0.0, round($cantidad - $conformadoPrimera, 2));
            }

            $segmentos = [];
            $ultimoFin = null;

            foreach ($rutas[$orden->articulo_id] ?? [] as $etapa) {
                $minutos = $cantidad * $etapa['tiempo_por_unidad'];
                $diasEtapa = max(1, (int) ceil($minutos / (60 * self::HORAS_JORNADA)));

                $fin = $cursor->copy()->addDays($diasEtapa - 1);
                $segmentos[] = [
                    'etapa' => $etapa['etapa'],
                    'inicio' => $cursor->toDateString(),
                    'fin' => $fin->toDateString(),
                    'dias' => $diasEtapa,
                ];

                $ultimoFin = $fin->copy();
                $cursor = $fin->copy()->addDay();
            }

            $fechaEntrega = $orden->fecha_entrega?->toDateString();

            $filas[] = [
                'orden_id' => $orden->id,
                'producto' => $resumenes[$orden->articulo_id]['descripcion'] ?? '',
                'estado' => $orden->estado,
                'prioridad' => (int) $orden->prioridad_actual,
                'fecha_entrega' => $fechaEntrega,
                'reubicable' => $orden->estado !== EstadoOrdenProduccion::EnCurso->value,
                'conflicto_entrega' => $fechaEntrega !== null
                    && $ultimoFin !== null
                    && $ultimoFin->greaterThan(Carbon::parse($fechaEntrega)),
                'etapas' => $segmentos,
            ];
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
