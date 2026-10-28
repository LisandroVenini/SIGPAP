<?php

namespace App\Modules\Inventario\Policies;

use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Services\GestionArticulosService;
use App\Modules\Usuarios\Models\Usuario;

class ArticuloPolicy
{
    public function __construct(private readonly GestionArticulosService $articulos) {}

    public function viewAny(Usuario $actor): bool
    {
        return $this->articulos->puedeGestionar($actor);
    }

    public function create(Usuario $actor): bool
    {
        return $this->articulos->puedeGestionar($actor);
    }

    public function update(Usuario $actor, Articulo $articulo): bool
    {
        return $this->articulos->puedeGestionar($actor);
    }

    public function cambiarEstado(Usuario $actor, Articulo $articulo): bool
    {
        return $this->articulos->puedeGestionar($actor);
    }

    public function ajustar(Usuario $actor, Articulo $articulo): bool
    {
        return $this->articulos->puedeGestionar($actor);
    }
}
