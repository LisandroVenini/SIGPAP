<?php

namespace App\Modules\Compras\Policies;

use App\Modules\Compras\Models\OrdenDeCompra;
use App\Modules\Compras\Services\GestionOrdenesDeCompraService;
use App\Modules\Usuarios\Models\Usuario;

class OrdenDeCompraPolicy
{
    public function __construct(private readonly GestionOrdenesDeCompraService $ordenes) {}

    public function viewAny(Usuario $actor): bool
    {
        return $this->ordenes->puedeGestionar($actor);
    }

    public function create(Usuario $actor): bool
    {
        return $this->ordenes->puedeGestionar($actor);
    }

    public function update(Usuario $actor, OrdenDeCompra $orden): bool
    {
        return $this->ordenes->puedeGestionar($actor);
    }

    public function delete(Usuario $actor, OrdenDeCompra $orden): bool
    {
        return $this->ordenes->puedeGestionar($actor);
    }
}
