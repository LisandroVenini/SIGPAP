<?php

namespace App\Modules\Inventario\Services;

use App\Modules\Inventario\Enums\EstadoArticulo;
use App\Modules\Inventario\Enums\TipoArticulo;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Queries\ConsumoPorArticuloQuery;
use App\Modules\Inventario\Queries\ReservasQuery;

/**
 * Read-only public contract of the Inventario module.
 *
 * Supplies the raw stock facts that the predictive engine turns into a supply
 * risk list. It never decides; it exposes state.
 */
class ConsultaInventarioService
{
    public function __construct(
        private readonly ReservasQuery $reservas,
        private readonly ConsumoPorArticuloQuery $consumo,
    ) {}

    /**
     * @return array{
     *     articulos: array<int, array{id:int, codigo_sku:string, descripcion:string, tipo:string, stock_fisico:float, stock_minimo:float, stock_maximo:float, comprometido:float, disponible:float, consumo_diario:float, dias_consumo:int}>,
     *     generado_en: string,
     * }
     */
    public function datosDeInventario(int $diasConsumo = 90): array
    {
        $diasConsumo = max(1, $diasConsumo);

        $articulos = Articulo::query()
            ->whereIn('tipo', [TipoArticulo::MateriaPrima->value, TipoArticulo::InsumoConsumible->value])
            ->where('estado', EstadoArticulo::Activo->value)
            ->orderBy('descripcion')
            ->get(['id', 'codigo_sku', 'descripcion', 'tipo', 'stock_fisico', 'stock_minimo', 'stock_maximo']);

        $ids = $articulos->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $comprometido = $this->reservas->comprometidoPorArticulo($ids);
        $consumido = $this->consumo->consumidoEntre(
            $ids,
            now()->subDays($diasConsumo)->startOfDay(),
            now()->startOfDay(),
        );

        return [
            'articulos' => $articulos
                ->map(fn (Articulo $articulo): array => [
                    'id' => (int) $articulo->id,
                    'codigo_sku' => (string) $articulo->codigo_sku,
                    'descripcion' => (string) $articulo->descripcion,
                    'tipo' => (string) $articulo->tipo,
                    'stock_fisico' => (float) $articulo->stock_fisico,
                    'stock_minimo' => (float) $articulo->stock_minimo,
                    'stock_maximo' => (float) $articulo->stock_maximo,
                    'comprometido' => (float) ($comprometido[$articulo->id] ?? 0.0),
                    'disponible' => round(
                        (float) $articulo->stock_fisico - (float) ($comprometido[$articulo->id] ?? 0.0),
                        2,
                    ),
                    'consumo_diario' => round(($consumido[$articulo->id] ?? 0.0) / $diasConsumo, 4),
                    'dias_consumo' => $diasConsumo,
                ])
                ->values()
                ->all(),
            'generado_en' => now()->toDateTimeString(),
        ];
    }
}
