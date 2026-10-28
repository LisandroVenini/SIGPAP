<?php

use App\Modules\Usuarios\Controllers\AuthenticatedSessionController;
use App\Modules\Usuarios\Controllers\ConfiguracionController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    });

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->middleware('auth')
        ->name('logout');

    Route::middleware('auth')
        ->prefix('usuarios')
        ->name('usuarios.')
        ->group(function (): void {
            Route::get('configuracion', [ConfiguracionController::class, 'index'])->name('configuracion');
            Route::put('configuracion', [ConfiguracionController::class, 'update'])->name('configuracion.update');
        });
});
