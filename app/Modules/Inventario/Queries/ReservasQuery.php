<?php

namespace App\Modules\Inventario\Queries;

use App\Modules\Inventario\Enums\EstadoReserva;
use App\Modules\Inventario\Models\Reserva;

class ReservasQuery
{
    /**
     * Sum the current reservations per article.
     *
     * @param  array<int, int>  $articuloIds
     * @return array<int, float>
     */
    public function comprometidoPorArticulo(array $articuloIds): array
    {
        if ($articuloIds === []) {
            return [];
        }

        return Reserva::query()
            ->whereIn('articulo_id', $articuloIds)
            ->where('estado', EstadoReserva::Vigente->value)
            ->selectRaw('articulo_id, sum(cantidad) as total')
            ->groupBy('articulo_id')
            ->pluck('total', 'articulo_id')
            ->mapWithKeys(fn (int|string $total, int|string $articuloId): array => [
                (int) $articuloId => (float) $total,
            ])
            ->all();
    }

    /**
     * Sum the current reservations for a single order grouped by article.
     *
     * @return array<int, float>
     */
    public function comprometidoDeOrden(int $ordenDeProduccionId): array
    {
        return Reserva::query()
            ->where('orden_de_produccion_id', $ordenDeProduccionId)
            ->where('estado', EstadoReserva::Vigente->value)
            ->selectRaw('articulo_id, sum(cantidad) as total')
            ->groupBy('articulo_id')
            ->pluck('total', 'articulo_id')
            ->mapWithKeys(fn (int|string $total, int|string $articuloId): array => [
                (int) $articuloId => (float) $total,
            ])
            ->all();
    }
}
