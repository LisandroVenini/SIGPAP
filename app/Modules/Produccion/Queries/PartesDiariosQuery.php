<?php

namespace App\Modules\Produccion\Queries;

use App\Modules\Produccion\Models\ParteDiario;
use Illuminate\Support\Collection;

class PartesDiariosQuery
{
    /**
     * @return Collection<int, array{id:int, etapa:string, fecha:string, cantidad:float, maquinaria:string|null, observaciones:string|null}>
     */
    public function porOrden(int $ordenId): Collection
    {
        return ParteDiario::query()
            ->join('etapas', 'etapas.id', '=', 'partes_diarios.etapa_id')
            ->leftJoin('maquinarias', 'maquinarias.id', '=', 'partes_diarios.maquinaria_id')
            ->where('partes_diarios.orden_de_produccion_id', $ordenId)
            ->orderBy('partes_diarios.fecha', 'desc')
            ->orderBy('partes_diarios.id', 'desc')
            ->select(
                'partes_diarios.id',
                'etapas.nombre as etapa',
                'partes_diarios.fecha',
                'partes_diarios.cantidad_conformada as cantidad',
                'maquinarias.nombre as maquinaria',
                'partes_diarios.observaciones',
            )
            ->get()
            ->map(fn ($fila): array => [
                'id' => (int) $fila->id,
                'etapa' => $fila->etapa,
                'fecha' => $fila->fecha->toDateString(),
                'cantidad' => (float) $fila->cantidad,
                'maquinaria' => $fila->maquinaria,
                'observaciones' => $fila->observaciones,
            ]);
    }

    /**
     * @return array<int, float>
     */
    public function conformadoPorEtapa(int $ordenId): array
    {
        return ParteDiario::query()
            ->where('orden_de_produccion_id', $ordenId)
            ->selectRaw('etapa_id, sum(cantidad_conformada) as total')
            ->groupBy('etapa_id')
            ->pluck('total', 'etapa_id')
            ->mapWithKeys(fn (int|string $total, int|string $etapaId): array => [
                (int) $etapaId => (float) $total,
            ])
            ->all();
    }

    /**
     * @param  array<int, int>  $ordenIds
     * @return array<int, Collection<int, array{id:int, etapa:string, fecha:string, cantidad:float, maquinaria:string|null, observaciones:string|null}>>
     */
    public function porOrdenes(array $ordenIds): array
    {
        if ($ordenIds === []) {
            return [];
        }

        $filas = ParteDiario::query()
            ->join('etapas', 'etapas.id', '=', 'partes_diarios.etapa_id')
            ->leftJoin('maquinarias', 'maquinarias.id', '=', 'partes_diarios.maquinaria_id')
            ->whereIn('partes_diarios.orden_de_produccion_id', $ordenIds)
            ->orderBy('partes_diarios.fecha', 'desc')
            ->orderBy('partes_diarios.id', 'desc')
            ->select(
                'partes_diarios.orden_de_produccion_id',
                'partes_diarios.id',
                'etapas.nombre as etapa',
                'partes_diarios.fecha',
                'partes_diarios.cantidad_conformada as cantidad',
                'maquinarias.nombre as maquinaria',
                'partes_diarios.observaciones',
            )
            ->get();

        return $filas
            ->groupBy('orden_de_produccion_id')
            ->mapWithKeys(fn ($grupo, int|string $ordenId): array => [
                (int) $ordenId => $grupo->map(fn ($fila): array => [
                    'id' => (int) $fila->id,
                    'etapa' => $fila->etapa,
                    'fecha' => $fila->fecha->toDateString(),
                    'cantidad' => (float) $fila->cantidad,
                    'maquinaria' => $fila->maquinaria,
                    'observaciones' => $fila->observaciones,
                ])->values(),
            ])
            ->all();
    }

    /**
     * @param  array<int, int>  $ordenIds
     * @return array<int, array<int, float>>
     */
    public function conformadoPorOrdenes(array $ordenIds): array
    {
        if ($ordenIds === []) {
            return [];
        }

        return ParteDiario::query()
            ->whereIn('orden_de_produccion_id', $ordenIds)
            ->selectRaw('orden_de_produccion_id, etapa_id, sum(cantidad_conformada) as total')
            ->groupBy('orden_de_produccion_id', 'etapa_id')
            ->get()
            ->groupBy('orden_de_produccion_id')
            ->mapWithKeys(fn ($grupo, int|string $ordenId): array => [
                (int) $ordenId => $grupo->mapWithKeys(fn ($fila): array => [
                    (int) $fila->etapa_id => (float) $fila->total,
                ])->all(),
            ])
            ->all();
    }
}
