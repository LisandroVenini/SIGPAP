<?php

namespace App\Modules\Inventario\Queries;

use App\Modules\Inventario\Enums\TipoMovimientoStock;
use App\Modules\Inventario\Models\MovimientoDeStock;
use Illuminate\Support\Collection;

class IngresosRegistradosQuery
{
    /**
     * Receipts grouped by purchase order and remito.
     *
     * @return Collection<int, array{orden_id:int, numero_remito:string, fecha:string|null, lineas:int, unidades:float}>
     */
    public function listar(): Collection
    {
        return MovimientoDeStock::query()
            ->where('tipo', TipoMovimientoStock::Ingreso->value)
            ->whereNotNull('numero_remito')
            ->selectRaw('orden_de_compra_id as orden_id, numero_remito, max(fecha) as fecha, count(*) as lineas, sum(cantidad) as unidades')
            ->groupBy('orden_de_compra_id', 'numero_remito')
            ->orderByDesc('fecha')
            ->get()
            ->map(fn ($fila): array => [
                'orden_id' => (int) $fila->orden_id,
                'numero_remito' => (string) $fila->numero_remito,
                'fecha' => $fila->fecha,
                'lineas' => (int) $fila->lineas,
                'unidades' => (float) $fila->unidades,
            ]);
    }
}
