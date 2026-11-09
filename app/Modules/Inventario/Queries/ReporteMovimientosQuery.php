<?php

namespace App\Modules\Inventario\Queries;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReporteMovimientosQuery
{
    /**
     * Stock movements whose date falls inside the requested range.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function filas(string $desde, string $hasta): Collection
    {
        return DB::table('movimientos_de_stock as movimientos')
            ->join('articulos as articulos', 'articulos.id', '=', 'movimientos.articulo_id')
            ->whereDate('movimientos.fecha', '>=', $desde)
            ->whereDate('movimientos.fecha', '<=', $hasta)
            ->orderBy('movimientos.fecha')
            ->orderBy('movimientos.id')
            ->get([
                'movimientos.fecha',
                'articulos.codigo_sku',
                'articulos.descripcion',
                'movimientos.tipo',
                'movimientos.cantidad',
                'movimientos.numero_remito',
                'movimientos.motivo',
            ])
            ->map(fn (object $fila): array => [
                'fecha' => Carbon::parse($fila->fecha)->toDateString(),
                'articulo' => $fila->codigo_sku.' - '.$fila->descripcion,
                'tipo' => $fila->tipo,
                'cantidad' => (float) $fila->cantidad,
                'numero_remito' => $fila->numero_remito,
                'motivo' => $fila->motivo,
            ]);
    }
}
