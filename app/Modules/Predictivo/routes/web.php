<?php

use App\Modules\Predictivo\Controllers\SimuladorController;
use App\Modules\Predictivo\Controllers\TableroPredictivoController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')
    ->prefix('predictivo')
    ->name('predictivo.')
    ->group(function (): void {
        Route::middleware('auth')->group(function (): void {
            Route::get('tablero', [TableroPredictivoController::class, 'index'])
                ->name('tablero.index');

            Route::get('simulador', [SimuladorController::class, 'index'])
                ->name('simulador.index');
            Route::post('simulador', [SimuladorController::class, 'store'])
                ->name('simulador.store');
        });
    });
