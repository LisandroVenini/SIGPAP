<?php

namespace App\Modules\Predictivo\Providers;

use App\Modules\Predictivo\Services\MLEngine;
use App\Modules\Predictivo\Services\ProcesoMLEngine;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class PredictivoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MLEngine::class, function (Application $app): ProcesoMLEngine {
            /** @var array{binary: string, script: string, timeout: int, training_timeout: int} $config */
            $config = (array) $app['config']->get('predictivo.ml_engine', []);

            return new ProcesoMLEngine($config);
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }
}
