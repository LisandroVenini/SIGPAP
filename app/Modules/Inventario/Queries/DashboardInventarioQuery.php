<?php

namespace App\Modules\Inventario\Queries;

use App\Modules\Inventario\Enums\EstadoArticulo;
use App\Modules\Inventario\Enums\EstadoReserva;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Models\Reserva;

class DashboardInventarioQuery
{
    /**
     * Active article counters in a single query.
     *
     * @return array{activos: int, bajo_minimo: int}
     */
    public function articulos(): array
    {
        $fila = Articulo::query()
            ->where('estado', EstadoArticulo::Activo->value)
            ->selectRaw('count(*) as activos')
            ->selectRaw('count(*) filter (where stock_minimo is not null and stock_fisico < stock_minimo) as bajo_minimo')
            ->first();

        return [
            'activos' => (int) $fila->activos,
            'bajo_minimo' => (int) $fila->bajo_minimo,
        ];
    }

    /**
     * Total stock currently committed to open reservations.
     */
    public function comprometido(): float
    {
        return (float) Reserva::query()
            ->where('estado', EstadoReserva::Vigente->value)
            ->sum('cantidad');
    }
}
