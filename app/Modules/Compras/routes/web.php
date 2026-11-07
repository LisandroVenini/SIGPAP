<?php

use App\Modules\Compras\Controllers\PortalCotizacionController;
use App\Modules\Compras\Controllers\ProveedorController;
use App\Modules\Compras\Controllers\SolicitudDeCotizacionController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::get('portal/cotizaciones/{token}', [PortalCotizacionController::class, 'show'])
        ->middleware('throttle:20,1')
        ->name('portal.cotizaciones.show');
    Route::post('portal/cotizaciones/{token}', [PortalCotizacionController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('portal.cotizaciones.store');

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
            });
        });
});
