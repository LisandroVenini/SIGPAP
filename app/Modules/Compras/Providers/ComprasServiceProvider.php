<?php

namespace App\Modules\Compras\Providers;

use Illuminate\Support\ServiceProvider;

class ComprasServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }
}
