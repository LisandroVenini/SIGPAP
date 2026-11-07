<?php

namespace App\Modules\Produccion\Policies;

use App\Modules\Produccion\Models\PlanDeMantenimiento;
use App\Modules\Produccion\Services\GestionPlanesDeMantenimientoService;
use App\Modules\Usuarios\Models\Usuario;

class PlanDeMantenimientoPolicy
{
    public function __construct(private readonly GestionPlanesDeMantenimientoService $planes) {}

    public function viewAny(Usuario $actor): bool
    {
        return $this->planes->puedeGestionar($actor);
    }

    public function create(Usuario $actor): bool
    {
        return $this->planes->puedeGestionar($actor);
    }

    public function update(Usuario $actor, PlanDeMantenimiento $plan): bool
    {
        return $this->planes->puedeGestionar($actor);
    }

    public function delete(Usuario $actor, PlanDeMantenimiento $plan): bool
    {
        return $this->planes->puedeGestionar($actor);
    }
}
