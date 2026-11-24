<?php

namespace App\Modules\Produccion\Queries;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PartesHistoricosQuery
{
    /**
     * Daily parts joined with the standard time of the article route, so the
     * model can normalize stages produced for different products.
     *
     * @return Collection<int, object{etapa_id:int, etapa:string, fecha:string, cantidad_conformada:float|string, tiempo_por_unidad:float|string}>
     */
    public function entre(string $desde, string $hasta): Collection
    {
        return DB::table('partes_diarios')
            ->join('ordenes_de_produccion', 'ordenes_de_produccion.id', '=', 'partes_diarios.orden_de_produccion_id')
            ->join('asignaciones_de_etapa', function ($join): void {
                $join->on('asignaciones_de_etapa.articulo_id', '=', 'ordenes_de_produccion.articulo_id')
                    ->on('asignaciones_de_etapa.etapa_id', '=', 'partes_diarios.etapa_id');
            })
            ->join('etapas', 'etapas.id', '=', 'partes_diarios.etapa_id')
            ->whereBetween('partes_diarios.fecha', [$desde, $hasta])
            ->orderBy('partes_diarios.fecha')
            ->orderBy('partes_diarios.id')
            ->select(
                'partes_diarios.etapa_id',
                'etapas.nombre as etapa',
                'partes_diarios.fecha',
                'partes_diarios.cantidad_conformada',
                'asignaciones_de_etapa.tiempo_por_unidad',
            )
            ->get();
    }
}
