<?php

use App\Modules\Inventario\Controllers\AjusteInventarioController;
use App\Modules\Inventario\Controllers\ArticuloController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')
    ->prefix('inventario')
    ->name('inventario.')
    ->group(function (): void {
        Route::middleware('auth')->group(function (): void {
            Route::get('articulos', [ArticuloController::class, 'index'])->name('articulos.index');
            Route::post('articulos', [ArticuloController::class, 'store'])->name('articulos.store');
            Route::put('articulos/{articulo}', [ArticuloController::class, 'update'])->name('articulos.update');
            Route::patch('articulos/{articulo}/estado', [ArticuloController::class, 'cambiarEstado'])->name('articulos.estado');

            Route::get('ajustes', [AjusteInventarioController::class, 'index'])->name('ajustes.index');
            Route::patch('articulos/{articulo}/ajuste', [AjusteInventarioController::class, 'store'])->name('ajustes.store');
        });
    });
