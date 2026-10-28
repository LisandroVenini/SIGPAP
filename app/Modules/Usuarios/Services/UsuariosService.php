<?php

namespace App\Modules\Usuarios\Services;

use App\Modules\Usuarios\Models\Usuario;

class UsuariosService
{
    /**
     * Determine whether the user holds the requested permission.
     */
    public function autorizar(Usuario $usuario, string $operacion): bool
    {
        return $usuario->can($operacion);
    }
}
