<?php

namespace App\Modules\Produccion\Providers;

use Illuminate\Support\ServiceProvider;

class ProduccionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }
}
