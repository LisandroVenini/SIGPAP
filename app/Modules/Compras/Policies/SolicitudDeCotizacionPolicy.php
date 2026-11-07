<?php

namespace App\Modules\Compras\Policies;

use App\Modules\Compras\Models\SolicitudDeCotizacion;
use App\Modules\Compras\Services\GestionSolicitudesDeCotizacionService;
use App\Modules\Usuarios\Models\Usuario;

class SolicitudDeCotizacionPolicy
{
    public function __construct(private readonly GestionSolicitudesDeCotizacionService $solicitudes) {}

    public function viewAny(Usuario $actor): bool
    {
        return $this->solicitudes->puedeGestionar($actor);
    }

    public function create(Usuario $actor): bool
    {
        return $this->solicitudes->puedeGestionar($actor);
    }

    public function update(Usuario $actor, SolicitudDeCotizacion $solicitud): bool
    {
        return $this->solicitudes->puedeGestionar($actor);
    }

    public function delete(Usuario $actor, SolicitudDeCotizacion $solicitud): bool
    {
        return $this->solicitudes->puedeGestionar($actor);
    }
}
