<?php

use App\Modules\Auditoria\Controllers\RegistroAuditoriaController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')
    ->prefix('auditoria')
    ->name('auditoria.')
    ->group(function (): void {
        Route::middleware('auth')
            ->get('/', [RegistroAuditoriaController::class, 'index'])
            ->name('index');
    });
