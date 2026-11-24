<?php

namespace App\Modules\Inventario\Jobs;

use App\Modules\Inventario\Services\EvaluarRiesgoDeSuministroService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Periodic supply risk evaluation, triggered by the scheduler.
 */
class EvaluarRiesgoSuministroJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    /** @var array<int, int> */
    public array $backoff = [60];

    public function handle(EvaluarRiesgoDeSuministroService $servicio): void
    {
        $resultado = $servicio->evaluar();

        Log::info('Supply risk evaluated.', $resultado);
    }
}
