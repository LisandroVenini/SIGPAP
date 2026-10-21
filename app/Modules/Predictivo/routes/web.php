<?php

use Illuminate\Support\Facades\Route;

Route::middleware('web')
    ->prefix('predictivo')
    ->name('predictivo.')
    ->group(function (): void {
        // Module routes are registered here.
    });
