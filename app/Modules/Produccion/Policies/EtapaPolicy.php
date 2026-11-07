<?php

namespace App\Modules\Produccion\Policies;

use App\Modules\Produccion\Models\Etapa;
use App\Modules\Produccion\Services\GestionEtapasService;
use App\Modules\Usuarios\Models\Usuario;

class EtapaPolicy
{
    public function __construct(private readonly GestionEtapasService $etapas) {}

    public function viewAny(Usuario $actor): bool
    {
        return $this->etapas->puedeGestionar($actor);
    }

    public function create(Usuario $actor): bool
    {
        return $this->etapas->puedeGestionar($actor);
    }

    public function update(Usuario $actor, Etapa $etapa): bool
    {
        return $this->etapas->puedeGestionar($actor);
    }

    public function delete(Usuario $actor, Etapa $etapa): bool
    {
        return $this->etapas->puedeGestionar($actor);
    }
}
