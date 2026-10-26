<?php

namespace App\Modules\Usuarios\Services;

use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\ParametroGlobal;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Auth\Access\AuthorizationException;

class ParametroGlobalService
{
    /**
     * Read a global parameter value. Transversal read access.
     */
    public function parametro(string $clave): ?string
    {
        return ParametroGlobal::where('nombre', $clave)->value('valor');
    }

    /**
     * Create or update a global parameter. Only an Administrador may write.
     */
    public function definirParametro(
        Usuario $actor,
        string $clave,
        string $valor,
        string $categoria = 'General',
    ): ParametroGlobal {
        if (! $actor->hasRole(Rol::Administrador->value)) {
            throw new AuthorizationException('Solo un Administrador puede definir parámetros globales.');
        }

        return ParametroGlobal::updateOrCreate(
            ['nombre' => $clave],
            ['valor' => $valor, 'categoria' => $categoria],
        );
    }
}
