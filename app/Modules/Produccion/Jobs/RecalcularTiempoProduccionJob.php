<?php

namespace App\Modules\Produccion\Jobs;

use App\Modules\Inventario\Services\GestionArticulosService;
use App\Modules\Predictivo\Services\PrediccionDeTiemposException;
use App\Modules\Predictivo\Services\PrediccionDeTiemposService;
use App\Shared\Alertas\Services\AlertaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * CU-PRED-02: react to a registered daily part.
 *
 * Producción is the origin of the event, so it owns the asynchronous work and
 * applies the result: the alert is emitted here through the shared kernel, not
 * by the predictive module.
 */
class RecalcularTiempoProduccionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120];

    public function __construct(
        public readonly int $ordenDeProduccionId,
        public readonly int $etapaId,
        public readonly string $fechaAvance,
    ) {}

    public function handle(
        PrediccionDeTiemposService $prediccion,
        AlertaService $alertas,
        GestionArticulosService $articulos,
    ): void {
        try {
            $resultado = $prediccion->recalcularTiempoProduccion(
                $this->ordenDeProduccionId,
                $this->etapaId,
                $this->fechaAvance,
            );
        } catch (PrediccionDeTiemposException $error) {
            Log::info('Predictive recalculation skipped.', [
                'orden_de_produccion_id' => $this->ordenDeProduccionId,
                'etapa_id' => $this->etapaId,
                'codigo' => $error->codigo,
            ]);

            return;
        }

        $referencia = 'orden:'.$this->ordenDeProduccionId;

        if (! $resultado['excede_plazo']) {
            $alertas->resolverPorTipo('cuello_de_botella_predictivo', $referencia);

            return;
        }

        $producto = $articulos
            ->resumenesDeArticulos([$resultado['articulo_id']])[$resultado['articulo_id']]['descripcion'] ?? null;

        $alertas->emitirPorTipo('cuello_de_botella_predictivo', [
            'orden' => $resultado['orden_de_produccion_id'],
            'producto' => $producto ?? 'Orden #'.$resultado['orden_de_produccion_id'],
            'etapa' => $resultado['etapa'],
            'fecha_entrega' => $resultado['fecha_entrega'],
            'fecha_proyectada' => $resultado['fecha_proyectada'],
            'dias_retraso' => $resultado['dias_de_retraso'],
            'tasa_rendimiento' => $resultado['valor'],
            'orden_de_produccion_id' => $resultado['orden_de_produccion_id'],
        ], $referencia);
    }
}
