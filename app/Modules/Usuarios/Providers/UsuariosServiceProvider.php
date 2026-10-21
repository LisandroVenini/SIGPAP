<?php

namespace App\Modules\Usuarios\Providers;

use Illuminate\Support\ServiceProvider;

class UsuariosServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }
}
