<?php

namespace App\Modules\Predictivo\Services;

use App\Modules\Predictivo\Models\TasaDeRendimiento;
use App\Modules\Predictivo\Queries\TasasDeRendimientoQuery;
use App\Modules\Produccion\Services\CalendarioLaboral;
use App\Modules\Produccion\Services\ConsultaProduccionService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * CU-PRED-02: recalculate the production time after a daily part.
 *
 * The module owns the calculation and its own `TasaDeRendimiento` concept. It
 * never touches production tables: the origin module applies the result.
 */
class PrediccionDeTiemposService
{
    /** Engine error codes that will not be fixed by retrying. */
    private const CODIGOS_PERMANENTES = [
        'campo_invalido',
        'cantidad_invalida',
        'muestras_invalidas',
        'muestra_invalida',
        'datos_invalidos',
        'tiempo_por_unidad_invalido',
    ];

    public function __construct(
        private readonly MLEngine $engine,
        private readonly CalendarioLaboral $calendario,
        private readonly ConsultaProduccionService $produccion,
        private readonly TasasDeRendimientoQuery $tasas,
    ) {}

    /**
     * Latest calculated rate per stage, for the schedule projection.
     *
     * @return array<int, array{valor: float, desviacion: float, fecha_calculo: string|null}>
     */
    public function tasaVigentePorEtapa(): array
    {
        return $this->tasas->ultimasPorEtapa();
    }

    /**
     * @return array{orden_de_produccion_id:int, articulo_id:int, etapa_id:int, etapa:string, valor:float, desviacion:float, dias_proyectados:float, fecha_proyectada:string, fecha_entrega:string|null, dias_de_retraso:int, excede_plazo:bool, origen:string, metricas:array{r2:float|null, mae:float|null, n_muestras:int}}
     *
     * @throws PrediccionDeTiemposException
     * @throws MLEngineException
     */
    public function recalcularTiempoProduccion(int $ordenDeProduccionId, int $etapaId, ?string $fechaAvance = null): array
    {
        $datos = $this->produccion->datosDeProduccion();

        $orden = $this->buscarOrden($datos['ordenes'], $ordenDeProduccionId);
        $ruta = $this->rutaDelProducto($datos['rutas'], (int) $orden['articulo_id']);
        $posicion = $this->posicionDeEtapa($ruta, $etapaId, (int) $orden['articulo_id']);

        $partesDeLaEtapa = $this->partesDeLaEtapa($datos['partes'], $ordenDeProduccionId, $etapaId);
        $conformado = (float) $partesDeLaEtapa->sum('cantidad_conformada');
        $rechazadas = (float) ($datos['rechazadas'][$ordenDeProduccionId][$etapaId] ?? 0.0);
        $pendiente = max(0.0, round((float) $orden['cantidad_requerida'] + $rechazadas - $conformado, 2));

        $fechaBase = $fechaAvance ?? now()->toDateString();
        $inicioTrabajo = $this->calendario->siguienteInicio(CarbonImmutable::parse($fechaBase)->startOfDay());
        $minutosBase = $this->calendario->minutosDelDia($inicioTrabajo);

        if ($minutosBase <= 0) {
            throw PrediccionDeTiemposException::datosInvalidos(
                'The working calendar has no available hours for the projection start.',
            );
        }

        $prediccion = $this->predecir([
            'etapa_id' => $etapaId,
            'tiempo_por_unidad' => (float) $ruta[$posicion]['tiempo_por_unidad'],
            'cantidad_pendiente' => $pendiente,
            'fecha_base' => $fechaBase,
            'minutos_habiles_por_dia' => $minutosBase,
            'muestras' => $partesDeLaEtapa
                ->map(fn (array $parte): array => [
                    'fecha' => $parte['fecha'],
                    'cantidad_conformada' => $parte['cantidad_conformada'],
                    'minutos_habiles' => $this->calendario->minutosDelDia(CarbonImmutable::parse($parte['fecha'])),
                ])
                ->values()
                ->all(),
        ]);

        TasaDeRendimiento::create([
            'orden_de_produccion_id' => $ordenDeProduccionId,
            'etapa_id' => $etapaId,
            'valor' => $prediccion['valor'],
            'desviacion' => $prediccion['desviacion'],
            'fecha_calculo' => $fechaBase,
        ]);

        $fechaProyectada = $this->fechaDeFinalizacion(
            $ruta,
            $posicion,
            $pendiente,
            (float) $orden['cantidad_requerida'],
            (float) $prediccion['valor'],
            $inicioTrabajo,
        );
        $fechaEntrega = $orden['fecha_entrega'] !== null
            ? CarbonImmutable::parse($orden['fecha_entrega'])->startOfDay()
            : null;

        return [
            'orden_de_produccion_id' => $ordenDeProduccionId,
            'articulo_id' => (int) $orden['articulo_id'],
            'etapa_id' => $etapaId,
            'etapa' => $ruta[$posicion]['etapa'],
            'valor' => (float) $prediccion['valor'],
            'desviacion' => (float) $prediccion['desviacion'],
            'dias_proyectados' => (float) $prediccion['dias_proyectados'],
            'fecha_proyectada' => $fechaProyectada->toDateString(),
            'fecha_entrega' => $orden['fecha_entrega'],
            'dias_de_retraso' => $this->diasDeRetraso($fechaProyectada->startOfDay(), $fechaEntrega),
            'excede_plazo' => $fechaEntrega !== null && $fechaProyectada->startOfDay()->greaterThan($fechaEntrega),
            'origen' => (string) $prediccion['origen'],
            'metricas' => $prediccion['metricas'],
        ];
    }

    /**
     * @param  array<int, array{id:int, articulo_id:int, estado:string, cantidad_requerida:float, cantidad_producida:float, fecha_entrega:string|null, fecha_inicio:string|null, fecha_fin:string|null, prioridad_actual:int}>  $ordenes
     * @return array{id:int, articulo_id:int, estado:string, cantidad_requerida:float, cantidad_producida:float, fecha_entrega:string|null, fecha_inicio:string|null, fecha_fin:string|null, prioridad_actual:int}
     */
    private function buscarOrden(array $ordenes, int $ordenId): array
    {
        $orden = collect($ordenes)->firstWhere('id', $ordenId);

        if ($orden === null) {
            throw PrediccionDeTiemposException::ordenNoDisponible($ordenId);
        }

        return $orden;
    }

    /**
     * @param  array<int, array<int, array{etapa_id:int, etapa:string, tiempo_por_unidad:float}>>  $rutas
     * @return array<int, array{etapa_id:int, etapa:string, tiempo_por_unidad:float}>
     */
    private function rutaDelProducto(array $rutas, int $articuloId): array
    {
        $ruta = $rutas[$articuloId] ?? [];

        if ($ruta === []) {
            throw PrediccionDeTiemposException::etapaFueraDeRuta(0, $articuloId);
        }

        foreach ($ruta as $etapa) {
            if ((float) $etapa['tiempo_por_unidad'] <= 0) {
                throw PrediccionDeTiemposException::datosInvalidos(
                    sprintf('The stage "%s" of the product %d has no valid standard time.', $etapa['etapa'], $articuloId),
                );
            }
        }

        return $ruta;
    }

    /**
     * @param  array<int, array{etapa_id:int, etapa:string, tiempo_por_unidad:float}>  $ruta
     */
    private function posicionDeEtapa(array $ruta, int $etapaId, int $articuloId): int
    {
        $posicion = array_search($etapaId, array_column($ruta, 'etapa_id'), true);

        if ($posicion === false) {
            throw PrediccionDeTiemposException::etapaFueraDeRuta($etapaId, $articuloId);
        }

        return (int) $posicion;
    }

    /**
     * @param  array<int, array<int, array{etapa_id:int, etapa:string, fecha:string, cantidad_conformada:float, maquinaria:string|null}>>  $partes
     * @return Collection<int, array{etapa_id:int, etapa:string, fecha:string, cantidad_conformada:float, maquinaria:string|null}>
     */
    private function partesDeLaEtapa(array $partes, int $ordenId, int $etapaId): Collection
    {
        return collect($partes[$ordenId] ?? [])
            ->where('etapa_id', $etapaId)
            ->values();
    }

    /**
     * @param  array{etapa_id:int, tiempo_por_unidad:float, cantidad_pendiente:float, fecha_base:string, minutos_habiles_por_dia:int, muestras:array<int, array{fecha:string, cantidad_conformada:float, minutos_habiles:int, tiempo_por_unidad?:float}>}  $datos
     * @return array{etapa_id:int, valor:float, desviacion:float, dias_proyectados:float, origen:string, metricas:array{r2:float|null, mae:float|null, n_muestras:int}}
     */
    private function predecir(array $datos): array
    {
        try {
            return $this->engine->predecirTasaDeRendimiento($datos);
        } catch (MLEngineException $error) {
            if (in_array($error->codigo, self::CODIGOS_PERMANENTES, true)) {
                throw PrediccionDeTiemposException::datosInvalidos($error->getMessage());
            }

            throw $error;
        }
    }

    /**
     * Exact finish of the order, walking the working calendar.
     *
     * The projected rate acts as a sustained level: the working minutes needed
     * by each remaining stage are `quantity × standard / rate`, and the
     * calendar turns those minutes into real working time. Integrating an
     * unbounded decay would produce absurd horizons.
     *
     * @param  array<int, array{etapa_id:int, etapa:string, tiempo_por_unidad:float}>  $ruta
     */
    private function fechaDeFinalizacion(
        array $ruta,
        int $posicion,
        float $pendiente,
        float $cantidadRequerida,
        float $valor,
        CarbonImmutable $inicio,
    ): CarbonImmutable {
        $tasa = max($valor, 0.0001);
        $minutos = 0.0;

        foreach (array_slice($ruta, $posicion) as $indice => $etapa) {
            $cantidad = $indice === 0 ? $pendiente : $cantidadRequerida;
            $minutos += ($cantidad * (float) $etapa['tiempo_por_unidad']) / $tasa;
        }

        return $this->calendario->avanzar($inicio, $minutos);
    }

    private function diasDeRetraso(CarbonImmutable $fechaProyectada, ?CarbonImmutable $fechaEntrega): int
    {
        if ($fechaEntrega === null) {
            return 0;
        }

        $diferencia = (int) ceil(($fechaProyectada->getTimestamp() - $fechaEntrega->getTimestamp()) / 86400);

        return max(0, $diferencia);
    }
}
