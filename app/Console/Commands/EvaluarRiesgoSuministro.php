<?php

namespace App\Console\Commands;

use App\Modules\Inventario\Jobs\EvaluarRiesgoSuministroJob;
use Illuminate\Console\Command;

class EvaluarRiesgoSuministro extends Command
{
    /**
     * @var string
     */
    protected $signature = 'riesgo:evaluar';

    /**
     * @var string
     */
    protected $description = 'Encola la evaluación periódica del riesgo de suministro.';

    public function handle(): int
    {
        EvaluarRiesgoSuministroJob::dispatch();

        $this->info('Evaluación de riesgo de suministro encolada.');

        return self::SUCCESS;
    }
}
