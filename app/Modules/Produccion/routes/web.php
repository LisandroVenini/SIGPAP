<?php

use App\Modules\Produccion\Controllers\EtapaController;
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
        });
    });
