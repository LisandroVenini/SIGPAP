<?php

namespace App\Modules\Compras\Queries;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReporteComprasQuery
{
    /**
     * Purchase orders whose issue date falls inside the requested range.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function filas(string $desde, string $hasta): Collection
    {
        return DB::table('ordenes_de_compra as ordenes')
            ->join('proveedores as proveedores', 'proveedores.id', '=', 'ordenes.proveedor_id')
            ->leftJoin('lineas_de_orden_de_compra as lineas', 'lineas.orden_de_compra_id', '=', 'ordenes.id')
            ->whereDate('ordenes.fecha_emision', '>=', $desde)
            ->whereDate('ordenes.fecha_emision', '<=', $hasta)
            ->groupBy(
                'ordenes.id',
                'ordenes.numero_oc',
                'proveedores.razon_social',
                'ordenes.estado',
                'ordenes.fecha_emision',
            )
            ->orderBy('ordenes.fecha_emision')
            ->orderBy('ordenes.id')
            ->get([
                'ordenes.numero_oc',
                'proveedores.razon_social as proveedor',
                'ordenes.estado',
                'ordenes.fecha_emision',
                DB::raw('count(lineas.id) as lineas'),
                DB::raw('coalesce(sum(lineas.cantidad * lineas.precio_pactado), 0) as total'),
            ])
            ->map(fn (object $fila): array => [
                'numero_oc' => $fila->numero_oc,
                'proveedor' => $fila->proveedor,
                'estado' => $fila->estado,
                'fecha_emision' => Carbon::parse($fila->fecha_emision)->toDateString(),
                'lineas' => (int) $fila->lineas,
                'total' => (float) $fila->total,
            ]);
    }
}
