<?php

use App\Modules\Compras\Controllers\ProveedorController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')
    ->prefix('compras')
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
        });
    });
