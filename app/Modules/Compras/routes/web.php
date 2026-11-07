<?php

use App\Modules\Compras\Controllers\OrdenDeCompraController;
use App\Modules\Compras\Controllers\PortalCotizacionController;
use App\Modules\Compras\Controllers\PortalOrdenController;
use App\Modules\Compras\Controllers\ProveedorController;
use App\Modules\Compras\Controllers\RankingController;
use App\Modules\Compras\Controllers\SolicitudDeCotizacionController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::get('portal/cotizaciones/{token}', [PortalCotizacionController::class, 'show'])
        ->middleware('throttle:20,1')
        ->name('portal.cotizaciones.show');
    Route::post('portal/cotizaciones/{token}', [PortalCotizacionController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('portal.cotizaciones.store');

    Route::get('portal/ordenes/{token}', [PortalOrdenController::class, 'show'])
        ->middleware('throttle:20,1')
        ->name('portal.ordenes.show');
    Route::post('portal/ordenes/{token}', [PortalOrdenController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('portal.ordenes.store');

    Route::prefix('compras')
        ->name('compras.')
        ->group(function (): void {
            Route::middleware('auth')->group(function (): void {
                Route::get('proveedores', [ProveedorController::class, 'index'])->name('proveedores.index');
                Route::post('proveedores', [ProveedorController::class, 'store'])->name('proveedores.store');
                Route::put('proveedores/{proveedor}', [ProveedorController::class, 'update'])
                    ->whereNumber('proveedor')
                    ->name('proveedores.update');
                Route::delete('proveedores/{proveedor}', [ProveedorController::class, 'destroy'])
                    ->whereNumber('proveedor')
                    ->name('proveedores.destroy');

                Route::get('solicitudes-de-cotizacion', [SolicitudDeCotizacionController::class, 'index'])
                    ->name('solicitudes-de-cotizacion.index');
                Route::post('solicitudes-de-cotizacion', [SolicitudDeCotizacionController::class, 'store'])
                    ->name('solicitudes-de-cotizacion.store');
                Route::put('solicitudes-de-cotizacion/{solicitud}', [SolicitudDeCotizacionController::class, 'update'])
                    ->whereNumber('solicitud')
                    ->name('solicitudes-de-cotizacion.update');
                Route::post('solicitudes-de-cotizacion/{solicitud}/anular', [SolicitudDeCotizacionController::class, 'anular'])
                    ->whereNumber('solicitud')
                    ->name('solicitudes-de-cotizacion.anular');

                Route::get('ordenes-de-compra', [OrdenDeCompraController::class, 'index'])
                    ->name('ordenes-de-compra.index');
                Route::post('ordenes-de-compra', [OrdenDeCompraController::class, 'store'])
                    ->name('ordenes-de-compra.store');
                Route::put('ordenes-de-compra/{orden}', [OrdenDeCompraController::class, 'update'])
                    ->whereNumber('orden')
                    ->name('ordenes-de-compra.update');
                Route::post('ordenes-de-compra/{orden}/anular', [OrdenDeCompraController::class, 'anular'])
                    ->whereNumber('orden')
                    ->name('ordenes-de-compra.anular');

                Route::get('ranking', [RankingController::class, 'index'])->name('ranking.index');
            });
        });
});
