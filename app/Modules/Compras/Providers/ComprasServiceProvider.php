<?php

namespace App\Modules\Compras\Providers;

use App\Modules\Compras\Models\OrdenDeCompra;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Models\SolicitudDeCotizacion;
use App\Modules\Compras\Policies\OrdenDeCompraPolicy;
use App\Modules\Compras\Policies\ProveedorPolicy;
use App\Modules\Compras\Policies\SolicitudDeCotizacionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ComprasServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        Gate::policy(Proveedor::class, ProveedorPolicy::class);
        Gate::policy(SolicitudDeCotizacion::class, SolicitudDeCotizacionPolicy::class);
        Gate::policy(OrdenDeCompra::class, OrdenDeCompraPolicy::class);
    }
}
