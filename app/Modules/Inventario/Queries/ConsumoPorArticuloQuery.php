<?php

namespace App\Modules\Inventario\Queries;

use App\Modules\Inventario\Enums\TipoMovimientoStock;
use App\Modules\Inventario\Models\MovimientoDeStock;
use Carbon\CarbonInterface;

class ConsumoPorArticuloQuery
{
    /**
     * Consumption quantity per article inside the window.
     *
     * @param  array<int, int>  $articuloIds
     * @return array<int, float>
     */
    public function consumidoEntre(array $articuloIds, CarbonInterface $desde, CarbonInterface $hasta): array
    {
        if ($articuloIds === []) {
            return [];
        }

        return MovimientoDeStock::query()
            ->whereIn('articulo_id', $articuloIds)
            ->where('tipo', TipoMovimientoStock::Consumo->value)
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->selectRaw('articulo_id, sum(cantidad) as total')
            ->groupBy('articulo_id')
            ->pluck('total', 'articulo_id')
            ->mapWithKeys(fn (int|string $total, int|string $articuloId): array => [
                (int) $articuloId => (float) $total,
            ])
            ->all();
    }
}
