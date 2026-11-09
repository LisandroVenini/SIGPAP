<?php

namespace App\Modules\Produccion\Queries;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReporteProduccionQuery
{
    /**
     * Production orders whose delivery date falls inside the requested range.
     *
     * The plan name comes from this module; the product description is resolved by the
     * service through Inventario's public service, never by joining a foreign table.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function filas(string $desde, string $hasta, ?string $estado): Collection
    {
        return DB::table('ordenes_de_produccion as ordenes')
            ->join('planificaciones_de_produccion as planificaciones', 'planificaciones.id', '=', 'ordenes.planificacion_de_produccion_id')
            ->whereDate('ordenes.fecha_entrega', '>=', $desde)
            ->whereDate('ordenes.fecha_entrega', '<=', $hasta)
            ->when($estado !== null && $estado !== '', fn ($query) => $query->where('ordenes.estado', $estado))
            ->orderBy('ordenes.fecha_entrega')
            ->orderBy('ordenes.id')
            ->get([
                'ordenes.id as orden_id',
                'ordenes.articulo_id',
                'planificaciones.nombre as planificacion',
                'ordenes.estado',
                'ordenes.cantidad_requerida',
                'ordenes.cantidad_producida',
                'ordenes.fecha_entrega',
                'ordenes.fecha_inicio',
                'ordenes.fecha_fin',
            ])
            ->map(fn (object $fila): array => [
                'orden_id' => (int) $fila->orden_id,
                'articulo_id' => (int) $fila->articulo_id,
                'planificacion' => $fila->planificacion,
                'estado' => $fila->estado,
                'cantidad_requerida' => (float) $fila->cantidad_requerida,
                'cantidad_producida' => (float) $fila->cantidad_producida,
                'fecha_entrega' => Carbon::parse($fila->fecha_entrega)->toDateString(),
                'fecha_inicio' => $fila->fecha_inicio !== null
                    ? Carbon::parse($fila->fecha_inicio)->toDateString()
                    : null,
                'fecha_fin' => $fila->fecha_fin !== null
                    ? Carbon::parse($fila->fecha_fin)->toDateString()
                    : null,
            ]);
    }
}
