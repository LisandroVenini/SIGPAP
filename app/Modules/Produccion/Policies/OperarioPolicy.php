<?php

namespace App\Modules\Produccion\Policies;

use App\Modules\Produccion\Models\Operario;
use App\Modules\Produccion\Services\GestionOperariosService;
use App\Modules\Usuarios\Models\Usuario;

class OperarioPolicy
{
    public function __construct(private readonly GestionOperariosService $operarios) {}

    public function viewAny(Usuario $actor): bool
    {
        return $this->operarios->puedeGestionar($actor);
    }

    public function create(Usuario $actor): bool
    {
        return $this->operarios->puedeGestionar($actor);
    }

    public function update(Usuario $actor, Operario $operario): bool
    {
        return $this->operarios->puedeGestionar($actor);
    }

    public function delete(Usuario $actor, Operario $operario): bool
    {
        return $this->operarios->puedeGestionar($actor);
    }
}
