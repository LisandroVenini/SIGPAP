<?php

use App\Modules\Alertas\Controllers\AlertaController;
use App\Modules\Alertas\Controllers\ReglaDeAlertaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('alertas')
    ->name('alertas.')
    ->group(function (): void {
        Route::get('/', [AlertaController::class, 'index'])->name('index');
        Route::patch('leidas', [AlertaController::class, 'marcarTodas'])->name('leidas');
        Route::patch('{alerta}/leida', [AlertaController::class, 'marcarLeida'])->name('leida');

        Route::get('reglas', [ReglaDeAlertaController::class, 'index'])->name('reglas.index');
        Route::post('reglas', [ReglaDeAlertaController::class, 'store'])->name('reglas.store');
        Route::put('reglas/{regla}', [ReglaDeAlertaController::class, 'update'])->name('reglas.update');
        Route::patch('reglas/{regla}/estado', [ReglaDeAlertaController::class, 'toggle'])->name('reglas.toggle');
        Route::delete('reglas/{regla}', [ReglaDeAlertaController::class, 'destroy'])->name('reglas.destroy');
    });
