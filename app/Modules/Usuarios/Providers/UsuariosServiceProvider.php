<?php

namespace App\Modules\Usuarios\Providers;

use App\Modules\Usuarios\Models\ParametroGlobal;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Policies\ParametroGlobalPolicy;
use App\Modules\Usuarios\Policies\UsuarioPolicy;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class UsuariosServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        Relation::morphMap([
            'usuario' => Usuario::class,
        ]);

        Gate::policy(Usuario::class, UsuarioPolicy::class);
        Gate::policy(ParametroGlobal::class, ParametroGlobalPolicy::class);
    }
}
