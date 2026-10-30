<?php

namespace App\Modules\Produccion\Policies;

use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Services\GestionOrdenesDeProduccionService;
use App\Modules\Usuarios\Models\Usuario;

class OrdenDeProduccionPolicy
{
    public function __construct(private readonly GestionOrdenesDeProduccionService $ordenes) {}

    public function viewAny(Usuario $actor): bool
    {
        return $this->ordenes->puedeGestionar($actor);
    }

    public function update(Usuario $actor, OrdenDeProduccion $orden): bool
    {
        return $this->ordenes->puedeGestionar($actor);
    }

    public function delete(Usuario $actor, OrdenDeProduccion $orden): bool
    {
        return $this->ordenes->puedeGestionar($actor);
    }
}
