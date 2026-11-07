<?php

namespace App\Modules\Produccion\Policies;

use App\Modules\Produccion\Models\PlanificacionDeProduccion;
use App\Modules\Produccion\Services\GestionPlanificacionesService;
use App\Modules\Usuarios\Models\Usuario;

class PlanificacionPolicy
{
    public function __construct(private readonly GestionPlanificacionesService $planificaciones) {}

    public function viewAny(Usuario $actor): bool
    {
        return $this->planificaciones->puedeGestionar($actor);
    }

    public function create(Usuario $actor): bool
    {
        return $this->planificaciones->puedeGestionar($actor);
    }

    public function update(Usuario $actor, PlanificacionDeProduccion $planificacion): bool
    {
        return $this->planificaciones->puedeGestionar($actor);
    }

    public function delete(Usuario $actor, PlanificacionDeProduccion $planificacion): bool
    {
        return $this->planificaciones->puedeGestionar($actor);
    }
}
