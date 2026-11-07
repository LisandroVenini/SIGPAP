<?php

namespace App\Modules\Compras\Providers;

use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Policies\ProveedorPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ComprasServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        Gate::policy(Proveedor::class, ProveedorPolicy::class);
    }
}
