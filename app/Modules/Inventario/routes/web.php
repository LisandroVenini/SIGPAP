<?php

use Illuminate\Support\Facades\Route;

Route::middleware('web')
    ->prefix('inventario')
    ->name('inventario.')
    ->group(function (): void {
        // Module routes are registered here.
    });
