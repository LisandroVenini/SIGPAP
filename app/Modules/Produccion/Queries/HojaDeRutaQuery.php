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

    /**
     * Return the stage names of each product's route sheet, ordered by name.
     *
     * @param  array<int, int>  $articuloIds
     * @return array<int, array<int, string>>
     */
    public function etapasPorArticulo(array $articuloIds): array
    {
        if ($articuloIds === []) {
            return [];
        }

        return AsignacionDeEtapa::query()
            ->join('etapas', 'etapas.id', '=', 'asignaciones_de_etapa.etapa_id')
            ->whereIn('asignaciones_de_etapa.articulo_id', $articuloIds)
            ->whereNull('etapas.deleted_at')
            ->orderBy('etapas.nombre')
            ->select('asignaciones_de_etapa.articulo_id', 'etapas.nombre')
            ->get()
            ->groupBy('articulo_id')
            ->map(fn ($filas): array => $filas->pluck('nombre')->values()->all())
            ->all();
    }
}
