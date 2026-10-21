<?php

use Illuminate\Support\Facades\Route;

Route::middleware('web')
    ->prefix('auditoria')
    ->name('auditoria.')
    ->group(function (): void {
        // Module routes are registered here.
    });
