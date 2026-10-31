<?php

namespace App\Modules\Produccion\Queries;

use App\Modules\Produccion\Models\ParteDiario;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PartesDiariosQuery
{
    /**
     * @return Collection<int, array{id:int, etapa_id:int, etapa:string, fecha:string, cantidad:float, maquinaria:string|null, observaciones:string|null}>
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
                'partes_diarios.etapa_id',
                'etapas.nombre as etapa',
                'partes_diarios.fecha',
                'partes_diarios.cantidad_conformada as cantidad',
                'maquinarias.nombre as maquinaria',
                'partes_diarios.observaciones',
            )
            ->get()
            ->map(fn ($fila): array => [
                'id' => (int) $fila->id,
                'etapa_id' => (int) $fila->etapa_id,
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
     * @return array<int, Collection<int, array{id:int, etapa_id:int, etapa:string, fecha:string, cantidad:float, maquinaria:string|null, observaciones:string|null}>>
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
                'partes_diarios.etapa_id',
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
                    'etapa_id' => (int) $fila->etapa_id,
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

    /**
     * Sum rejected quantities per stage for the given order.
     *
     * Only parte-level quality controls are considered because order-level
     * controls happen at closure and do not affect the daily-part ceiling.
     *
     * @return array<int, float>
     */
    public function rechazadasPorEtapa(int $ordenId): array
    {
        return DB::table('controles_de_calidad')
            ->join('partes_diarios', 'partes_diarios.id', '=', 'controles_de_calidad.parte_diario_id')
            ->where('controles_de_calidad.orden_de_produccion_id', $ordenId)
            ->whereNotNull('controles_de_calidad.parte_diario_id')
            ->selectRaw('partes_diarios.etapa_id, sum(controles_de_calidad.cantidad_rechazada) as total')
            ->groupBy('partes_diarios.etapa_id')
            ->pluck('total', 'partes_diarios.etapa_id')
            ->mapWithKeys(fn (int|string $total, int|string $etapaId): array => [
                (int) $etapaId => (float) $total,
            ])
            ->all();
    }
}
