<?php

namespace App\Modules\Predictivo\Providers;

use Illuminate\Support\ServiceProvider;

class PredictivoServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }
}
