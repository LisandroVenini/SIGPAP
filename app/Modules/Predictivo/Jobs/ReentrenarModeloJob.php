<?php

namespace App\Modules\Predictivo\Jobs;

use App\Modules\Predictivo\Services\MLEngineException;
use App\Modules\Predictivo\Services\ReentrenarModeloService;
use App\Shared\Alertas\Services\AlertaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * CU-PRED-01: scheduled retraining of the predictive model.
 */
class ReentrenarModeloJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 900;

    /** @var array<int, int> */
    public array $backoff = [3600];

    public function handle(ReentrenarModeloService $servicio, AlertaService $alertas): void
    {
        try {
            $resultado = $servicio->reentrenar();

            Log::info('Predictive model retrained.', $resultado);
        } catch (MLEngineException $error) {
            if ($servicio->esErrorTransitorio($error)) {
                $alertas->emitirPorTipo('reentrenamiento_fallido', [
                    'motivo' => $error->codigo === 'timeout'
                        ? 'Timeout del componente predictivo'
                        : 'Error del componente predictivo',
                ], 'reentrenamiento:'.now()->format('Y-m-d'));

                // The use case asks to reschedule on the next hourly cycle.
                $this->release(3600);

                return;
            }

            throw $error;
        }
    }
}
