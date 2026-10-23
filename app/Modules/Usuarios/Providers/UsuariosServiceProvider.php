<?php

namespace App\Modules\Usuarios\Providers;

use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class UsuariosServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        Relation::morphMap([
            'usuario' => Usuario::class,
        ]);
    }
}
