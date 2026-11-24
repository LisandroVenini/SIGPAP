<?php

namespace App\Modules\Inventario\Services;

use App\Modules\Compras\Services\GestionSolicitudesDeCotizacionService;
use App\Modules\Predictivo\Services\EvaluarRiesgoSuministroService;
use App\Shared\Alertas\Services\AlertaService;

/**
 * CU-PRED-03 origin process.
 *
 * Inventario owns the stock, so it is the module that reacts: it asks the
 * predictive module for the criticality list, emits or closes the stock-break
 * alerts through the shared kernel and asks Compras for a draft request per
 * critical input.
 */
class EvaluarRiesgoDeSuministroService
{
    public function __construct(
        private readonly EvaluarRiesgoSuministroService $predictivo,
        private readonly GestionSolicitudesDeCotizacionService $compras,
        private readonly AlertaService $alertas,
    ) {}

    /**
     * @return array{evaluados: int, criticos: int, alertas: int, borradores: int}
     */
    public function evaluar(?string $fechaBase = null): array
    {
        $riesgo = $this->predictivo->evaluar($fechaBase);

        $contextos = [];

        foreach ($riesgo['criticidad'] as $fila) {
            $contextos['articulo:'.$fila['articulo_id']] = [
                'articulo' => $fila['descripcion'] ?? 'Artículo '.$fila['articulo_id'],
                'disponible' => $fila['disponible'],
                'minimo' => $fila['stock_minimo'] ?? 0.0,
                'dias_hasta_quiebre' => $fila['dias_hasta_quiebre'],
                'fecha_limite_pedido' => $fila['fecha_limite_pedido'],
                'cantidad_sugerida' => $fila['cantidad_sugerida'],
                'articulo_id' => $fila['articulo_id'],
            ];
        }

        // Emits or refreshes one alert per critical input and closes the ones
        // that recovered; the risk list is exactly the set that must be open.
        $alertas = $this->alertas->sincronizarPorTipo('quiebre_de_stock', $contextos);

        $borradores = 0;

        foreach ($riesgo['criticidad'] as $fila) {
            $borrador = $this->compras->crearBorradorPorRiesgo(
                (int) $fila['articulo_id'],
                (float) $fila['cantidad_sugerida'],
            );

            if ($borrador !== null) {
                $borradores++;
            }
        }

        return [
            'evaluados' => (int) $riesgo['evaluados'],
            'criticos' => count($riesgo['criticidad']),
            'alertas' => $alertas,
            'borradores' => $borradores,
        ];
    }
}
