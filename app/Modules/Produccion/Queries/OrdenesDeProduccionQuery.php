<?php

namespace App\Modules\Produccion\Queries;

use App\Modules\Produccion\Models\OrdenDeProduccion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrdenesDeProduccionQuery
{
    /**
     * @param  array{articulo_ids?: array<int, int>|null, estado?: string|null, fecha_desde?: string|null, fecha_hasta?: string|null}  $filtros
     */
    public function listar(array $filtros = []): LengthAwarePaginator
    {
        return OrdenDeProduccion::query()
            ->with('planificacionDeProduccion')
            ->when(
                array_key_exists('articulo_ids', $filtros),
                fn ($query) => $query->buscarPorArticulos($filtros['articulo_ids'] ?? []),
            )
            ->when($filtros['estado'] ?? null, fn ($query, string $estado) => $query->conEstado($estado))
            ->when($filtros['fecha_desde'] ?? null, fn ($query, string $fecha) => $query->whereDate('fecha_entrega', '>=', $fecha))
            ->when($filtros['fecha_hasta'] ?? null, fn ($query, string $fecha) => $query->whereDate('fecha_entrega', '<=', $fecha))
            ->orderBy('fecha_entrega')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();
    }
}
