<?php

namespace App\Modules\Reportes\Providers;

use Illuminate\Support\ServiceProvider;

class ReportesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }
}
