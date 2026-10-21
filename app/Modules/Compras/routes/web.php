<?php

use Illuminate\Support\Facades\Route;

Route::middleware('web')
    ->prefix('compras')
    ->name('compras.')
    ->group(function (): void {
        // Module routes are registered here.
    });
