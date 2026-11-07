<?php

namespace App\Modules\Compras\Queries;

use App\Modules\Compras\Models\LineaOrdenCompra;
use App\Modules\Compras\Models\OrdenDeCompra;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class OrdenesDeCompraQuery
{
    /**
     * @param  array{numero_oc?: string|null, estado?: string|null, proveedor?: string|null, fecha_desde?: string|null, fecha_hasta?: string|null}  $filtros
     */
    public function listar(array $filtros = []): LengthAwarePaginator
    {
        return OrdenDeCompra::query()
            ->with('proveedor')
            ->withCount('lineas')
            ->addSelect([
                'total' => LineaOrdenCompra::query()
                    ->selectRaw('coalesce(sum(cantidad * precio_pactado), 0)')
                    ->whereColumn('orden_de_compra_id', 'ordenes_de_compra.id'),
            ])
            ->when(
                $filtros['numero_oc'] ?? null,
                fn (Builder $query, string $numero) => $query->where(
                    'numero_oc',
                    'ilike',
                    '%'.addcslashes($numero, '%_\\').'%',
                ),
            )
            ->when(
                $filtros['estado'] ?? null,
                fn (Builder $query, string $estado) => $query->where('estado', $estado),
            )
            ->when(
                $filtros['proveedor'] ?? null,
                fn (Builder $query, string $proveedor) => $query->whereHas(
                    'proveedor',
                    fn (Builder $query) => $query->where('razon_social', 'ilike', '%'.addcslashes($proveedor, '%_\\').'%'),
                ),
            )
            ->when(
                $filtros['fecha_desde'] ?? null,
                fn (Builder $query, string $fecha) => $query->whereDate('fecha_emision', '>=', $fecha),
            )
            ->when(
                $filtros['fecha_hasta'] ?? null,
                fn (Builder $query, string $fecha) => $query->whereDate('fecha_emision', '<=', $fecha),
            )
            ->orderByDesc('fecha_emision')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (OrdenDeCompra $orden): array => [
                'id' => $orden->id,
                'numero_oc' => $orden->numero_oc,
                'proveedor' => $orden->proveedor?->razon_social ?? '',
                'estado' => $orden->estado,
                'fecha_emision' => $orden->fecha_emision->format('Y-m-d'),
                'lineas' => $orden->lineas_count,
                'total' => (float) ($orden->total ?? 0),
            ]);
    }
}
