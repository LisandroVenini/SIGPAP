<?php

use Illuminate\Support\Facades\Route;

Route::middleware('web')
    ->prefix('usuarios')
    ->name('usuarios.')
    ->group(function (): void {
        // Module routes are registered here.
    });
