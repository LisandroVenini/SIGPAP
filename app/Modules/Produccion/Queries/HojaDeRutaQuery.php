<?php

namespace App\Modules\Produccion\Queries;

use App\Modules\Produccion\Models\AsignacionDeEtapa;
use Illuminate\Support\Collection;

class HojaDeRutaQuery
{
    /**
     * Count the etapas assigned to each product.
     *
     * @param  Collection<int, int>  $articuloIds
     * @return array<int, int>
     */
    public function contarEtapasPorArticulo(Collection $articuloIds): array
    {
        return AsignacionDeEtapa::query()
            ->whereIn('articulo_id', $articuloIds)
            ->selectRaw('articulo_id, count(*) as total')
            ->groupBy('articulo_id')
            ->pluck('total', 'articulo_id')
            ->mapWithKeys(fn (int|string $total, int|string $articuloId): array => [(int) $articuloId => (int) $total])
            ->all();
    }
}
