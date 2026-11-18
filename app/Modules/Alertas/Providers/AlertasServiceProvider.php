<?php

namespace App\Modules\Alertas\Providers;

use App\Modules\Alertas\Policies\ReglaDeAlertaPolicy;
use App\Shared\Alertas\Models\ReglaDeAlerta;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AlertasServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        Gate::policy(ReglaDeAlerta::class, ReglaDeAlertaPolicy::class);
    }
}
