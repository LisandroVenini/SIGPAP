<?php

namespace App\Modules\Compras\Queries;

use App\Modules\Compras\Models\OrdenDeCompra;
use Illuminate\Support\Collection;

class RecepcionesQuery
{
    /**
     * Orders waiting for the physical reception of their merchandise.
     *
     * @return Collection<int, OrdenDeCompra>
     */
    public function recepcionables(): Collection
    {
        return OrdenDeCompra::query()
            ->with(['proveedor', 'lineas'])
            ->recepcionables()
            ->orderBy('fecha_emision')
            ->orderBy('id')
            ->get();
    }
}
