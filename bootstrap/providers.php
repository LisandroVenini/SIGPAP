<?php

use App\Modules\Auditoria\Providers\AuditoriaServiceProvider;
use App\Modules\Compras\Providers\ComprasServiceProvider;
use App\Modules\Inventario\Providers\InventarioServiceProvider;
use App\Modules\Predictivo\Providers\PredictivoServiceProvider;
use App\Modules\Produccion\Providers\ProduccionServiceProvider;
use App\Modules\Reportes\Providers\ReportesServiceProvider;
use App\Modules\Usuarios\Providers\UsuariosServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    AuditoriaServiceProvider::class,
    ComprasServiceProvider::class,
    InventarioServiceProvider::class,
    PredictivoServiceProvider::class,
    ProduccionServiceProvider::class,
    ReportesServiceProvider::class,
    UsuariosServiceProvider::class,
];
