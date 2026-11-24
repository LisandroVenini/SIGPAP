<?php

namespace App\Console\Commands;

use App\Modules\Predictivo\Jobs\ReentrenarModeloJob;
use Illuminate\Console\Command;

class ReentrenarModeloPredictivo extends Command
{
    /**
     * @var string
     */
    protected $signature = 'predictivo:reentrenar';

    /**
     * @var string
     */
    protected $description = 'Encola el reentrenamiento periódico del modelo predictivo.';

    public function handle(): int
    {
        ReentrenarModeloJob::dispatch();

        $this->info('Reentrenamiento del modelo predictivo encolado.');

        return self::SUCCESS;
    }
}
