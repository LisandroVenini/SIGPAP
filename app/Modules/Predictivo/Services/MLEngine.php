<?php

namespace App\Modules\Predictivo\Services;

/**
 * Abstraction over the predictive calculation backend.
 *
 * The concrete implementation runs the Python engine as a process over
 * stdin/stdout. Consumers depend on this contract, never on the process itself.
 */
interface MLEngine
{
    /**
     * Project the Tasa de Rendimiento Real of a stage and the days needed to
     * finish the pending quantity.
     *
     * @param  array{etapa_id: int, tiempo_por_unidad: float, cantidad_pendiente: float, fecha_base: string, muestras: array<int, array{fecha: string, cantidad_conformada: float}>}  $datos
     * @return array{etapa_id: int, valor: float, desviacion: float, dias_proyectados: float, origen: string, metricas: array{r2: float|null, mae: float|null, n_muestras: int}}
     */
    public function predecirTasaDeRendimiento(array $datos): array;

    /**
     * Fit and persist the Tasa de Rendimiento model of a stage.
     *
     * @param  array{etapa_id: int, tiempo_por_unidad: float, muestras: array<int, array{fecha: string, cantidad_conformada: float}>}  $datos
     * @return array{etapa_id: int, metricas: array{r2: float|null, mae: float, n_muestras: int}, n_muestras: int, guardado: bool}
     */
    public function entrenar(array $datos): array;

    /**
     * Evaluate which inputs will run out of stock before a replacement arrives.
     *
     * @param  array{fecha_base: string, dias_aviso: int, insumos: array<int, array{articulo_id: int, stock_fisico: float, comprometido: float, consumo_diario: float, lead_time_dias: float, stock_minimo: float, stock_maximo: float}>}  $datos
     * @return array{criticidad: array<int, array<string, mixed>>, evaluados: int}
     */
    public function evaluarRiesgoSuministro(array $datos): array;
}
