<?php

use App\Modules\Reportes\Controllers\ReporteController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')
    ->prefix('reportes')
    ->name('reportes.')
    ->group(function (): void {
        Route::middleware('auth')->group(function (): void {
            Route::get('/', [ReporteController::class, 'index'])->name('index');
            Route::get('/exportar', [ReporteController::class, 'exportar'])->name('exportar');
        });
    });
