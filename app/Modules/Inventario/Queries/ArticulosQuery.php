<?php

namespace App\Modules\Inventario\Queries;

use App\Modules\Inventario\Enums\EstadoArticulo;
use App\Modules\Inventario\Enums\EstadoReserva;
use App\Modules\Inventario\Models\Articulo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ArticulosQuery
{
    /**
     * Paginate the article catalog with its filters and committed stock.
     *
     * @param  array{buscar?: string|null, tipo?: string|null, estado?: string|null}  $filtros
     */
    public function listar(array $filtros = []): LengthAwarePaginator
    {
        return Articulo::query()
            ->with('unidadDeMedida')
            ->withSum(['reservas as stock_comprometido' => fn ($query) => $query->where('estado', EstadoReserva::Vigente->value)], 'cantidad')
            ->when($filtros['buscar'] ?? null, function ($query, string $buscar): void {
                $query->where(function ($query) use ($buscar): void {
                    $query->where('codigo_sku', 'like', "%{$buscar}%")
                        ->orWhere('descripcion', 'like', "%{$buscar}%")
                        ->orWhere('tipo', 'like', "%{$buscar}%");
                });
            })
            ->when($filtros['tipo'] ?? null, fn ($query, string $tipo) => $query->tipo($tipo))
            ->when($filtros['estado'] ?? null, fn ($query, string $estado) => $query->estado($estado))
            ->orderBy('descripcion')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Articulo $articulo) => [
                'id' => $articulo->id,
                'codigo_sku' => $articulo->codigo_sku,
                'descripcion' => $articulo->descripcion,
                'tipo' => $articulo->tipo,
                'origen' => $articulo->origen,
                'estado' => $articulo->estado,
                'unidad_de_medida_id' => $articulo->unidad_de_medida_id,
                'unidad' => $articulo->unidadDeMedida?->nombre,
                'stock_fisico' => (float) $articulo->stock_fisico,
                'stock_comprometido' => (float) ($articulo->stock_comprometido ?? 0),
                'stock_disponible' => (float) $articulo->stock_fisico - (float) ($articulo->stock_comprometido ?? 0),
                'stock_minimo' => $articulo->stock_minimo !== null ? (float) $articulo->stock_minimo : null,
                'stock_maximo' => $articulo->stock_maximo !== null ? (float) $articulo->stock_maximo : null,
            ]);
    }

    /**
     * @return array{total: int, activos: int, inactivos: int}
     */
    public function resumen(): array
    {
        $total = Articulo::query()->count();
        $inactivos = Articulo::query()->estado(EstadoArticulo::Inactivo->value)->count();

        return [
            'total' => $total,
            'activos' => $total - $inactivos,
            'inactivos' => $inactivos,
        ];
    }
}
