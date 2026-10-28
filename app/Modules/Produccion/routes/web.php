<?php

use App\Modules\Produccion\Controllers\EtapaController;
use App\Modules\Produccion\Controllers\HojaDeRutaController;
use App\Modules\Produccion\Controllers\OperarioController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')
    ->prefix('produccion')
    ->name('produccion.')
    ->group(function (): void {
        Route::middleware('auth')->group(function (): void {
            Route::get('etapas', [EtapaController::class, 'index'])->name('etapas.index');
            Route::post('etapas', [EtapaController::class, 'store'])->name('etapas.store');
            Route::put('etapas/{etapa}', [EtapaController::class, 'update'])->name('etapas.update');
            Route::delete('etapas/{etapa}', [EtapaController::class, 'destroy'])->name('etapas.destroy');

            Route::get('operarios', [OperarioController::class, 'index'])->name('operarios.index');
            Route::post('operarios', [OperarioController::class, 'store'])->name('operarios.store');
            Route::put('operarios/{operario}', [OperarioController::class, 'update'])->name('operarios.update');
            Route::delete('operarios/{operario}', [OperarioController::class, 'destroy'])->name('operarios.destroy');

            Route::get('hoja-de-ruta', [HojaDeRutaController::class, 'index'])->name('hoja-de-ruta.index');
            Route::get('hoja-de-ruta/{articulo}', [HojaDeRutaController::class, 'show'])
                ->whereNumber('articulo')
                ->name('hoja-de-ruta.show');
            Route::post('hoja-de-ruta/{articulo}/etapas', [HojaDeRutaController::class, 'store'])
                ->whereNumber('articulo')
                ->name('hoja-de-ruta.etapas.store');
            Route::delete('hoja-de-ruta/{articulo}/etapas/{etapa}', [HojaDeRutaController::class, 'destroy'])
                ->whereNumber('articulo')
                ->whereNumber('etapa')
                ->name('hoja-de-ruta.etapas.destroy');
        });
    });
