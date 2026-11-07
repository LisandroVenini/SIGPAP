<?php

namespace App\Modules\Compras\Policies;

use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Services\GestionProveedoresService;
use App\Modules\Usuarios\Models\Usuario;

class ProveedorPolicy
{
    public function __construct(private readonly GestionProveedoresService $proveedores) {}

    public function viewAny(Usuario $actor): bool
    {
        return $this->proveedores->puedeGestionar($actor);
    }

    public function create(Usuario $actor): bool
    {
        return $this->proveedores->puedeGestionar($actor);
    }

    public function update(Usuario $actor, Proveedor $proveedor): bool
    {
        return $this->proveedores->puedeGestionar($actor);
    }

    public function delete(Usuario $actor, Proveedor $proveedor): bool
    {
        return $this->proveedores->puedeGestionar($actor);
    }
}
