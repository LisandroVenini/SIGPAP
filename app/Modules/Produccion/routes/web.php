<?php

use Illuminate\Support\Facades\Route;

Route::middleware('web')
    ->prefix('produccion')
    ->name('produccion.')
    ->group(function (): void {
        // Module routes are registered here.
    });
