<?php

namespace App\Modules\Usuarios\Policies;

use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\GestionUsuariosService;

class UsuarioPolicy
{
    public function __construct(private readonly GestionUsuariosService $usuarios) {}

    public function viewAny(Usuario $actor): bool
    {
        return $this->usuarios->puedeGestionar($actor);
    }

    public function create(Usuario $actor): bool
    {
        return $this->usuarios->puedeGestionar($actor);
    }

    public function update(Usuario $actor, Usuario $usuario): bool
    {
        return $this->usuarios->puedeGestionar($actor);
    }

    public function cambiarEstado(Usuario $actor, Usuario $usuario): bool
    {
        return $this->usuarios->puedeGestionar($actor);
    }
}
