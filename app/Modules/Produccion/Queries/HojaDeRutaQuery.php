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

    /**
     * Return the stages of each product's route sheet with their ids, ordered by name.
     *
     * @param  array<int, int>  $articuloIds
     * @return array<int, array<int, array{id:int, nombre:string}>>
     */
    public function etapasConIdPorArticulo(array $articuloIds): array
    {
        if ($articuloIds === []) {
            return [];
        }

        return AsignacionDeEtapa::query()
            ->join('etapas', 'etapas.id', '=', 'asignaciones_de_etapa.etapa_id')
            ->whereIn('asignaciones_de_etapa.articulo_id', $articuloIds)
            ->whereNull('etapas.deleted_at')
            ->orderBy('etapas.nombre')
            ->select('asignaciones_de_etapa.articulo_id', 'asignaciones_de_etapa.etapa_id as id', 'etapas.nombre')
            ->get()
            ->groupBy('articulo_id')
            ->map(fn ($filas): array => $filas->map(fn ($fila): array => [
                'id' => (int) $fila->id,
                'nombre' => $fila->nombre,
            ])->values()->all())
            ->all();
    }

    /**
     * Return the first stage id of each product's route sheet.
     *
     * @param  array<int, int>  $articuloIds
     * @return array<int, int>
     */
    public function primeraEtapaPorArticulo(array $articuloIds): array
    {
        if ($articuloIds === []) {
            return [];
        }

        return AsignacionDeEtapa::query()
            ->whereIn('articulo_id', $articuloIds)
            ->orderBy('id')
            ->orderBy('etapa_id')
            ->get()
            ->unique('articulo_id')
            ->mapWithKeys(fn (AsignacionDeEtapa $asignacion): array => [
                (int) $asignacion->articulo_id => (int) $asignacion->etapa_id,
            ])
            ->all();
    }
}
