<?php

namespace App\Modules\Inventario\Providers;

use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Policies\ArticuloPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class InventarioServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        Gate::policy(Articulo::class, ArticuloPolicy::class);
    }
}
