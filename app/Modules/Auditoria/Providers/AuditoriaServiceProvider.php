<?php

namespace App\Modules\Auditoria\Providers;

use Illuminate\Support\ServiceProvider;

class AuditoriaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }
}
