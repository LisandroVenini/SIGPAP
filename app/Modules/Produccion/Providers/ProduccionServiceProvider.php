<?php

namespace App\Modules\Produccion\Providers;

use App\Modules\Produccion\Models\Etapa;
use App\Modules\Produccion\Models\Operario;
use App\Modules\Produccion\Models\PlanificacionDeProduccion;
use App\Modules\Produccion\Policies\EtapaPolicy;
use App\Modules\Produccion\Policies\OperarioPolicy;
use App\Modules\Produccion\Policies\PlanificacionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ProduccionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        Gate::policy(Etapa::class, EtapaPolicy::class);
        Gate::policy(Operario::class, OperarioPolicy::class);
        Gate::policy(PlanificacionDeProduccion::class, PlanificacionPolicy::class);
    }
}
