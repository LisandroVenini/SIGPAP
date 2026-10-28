<?php

use App\Modules\Produccion\Controllers\EtapaController;
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
        });
    });
