<?php

namespace App\Modules\Produccion\Policies;

use App\Modules\Produccion\Models\Maquinaria;
use App\Modules\Produccion\Services\GestionMaquinariasService;
use App\Modules\Usuarios\Models\Usuario;

class MaquinariaPolicy
{
    public function __construct(private readonly GestionMaquinariasService $maquinarias) {}

    public function viewAny(Usuario $actor): bool
    {
        return $this->maquinarias->puedeGestionar($actor);
    }

    public function create(Usuario $actor): bool
    {
        return $this->maquinarias->puedeGestionar($actor);
    }

    public function update(Usuario $actor, Maquinaria $maquinaria): bool
    {
        return $this->maquinarias->puedeGestionar($actor);
    }

    public function delete(Usuario $actor, Maquinaria $maquinaria): bool
    {
        return $this->maquinarias->puedeGestionar($actor);
    }
}
