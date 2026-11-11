<?php

namespace App\Modules\Inventario\Database\Seeders;

use App\Modules\Inventario\Enums\EstadoArticulo;
use App\Modules\Inventario\Enums\OrigenArticulo;
use App\Modules\Inventario\Enums\TipoArticulo;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Models\UnidadDeMedida;
use Database\Seeders\UnidadDeMedidaSeeder;
use Illuminate\Database\Seeder;

/**
 * Minimal catalog the production module needs: raw materials with stock and
 * the finished products that own a route sheet.
 *
 * Articles are created once and never overwritten, so re-running the seeder
 * does not refill stock that production already consumed.
 */
class InventarioBaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(UnidadDeMedidaSeeder::class);

        $unidad = fn (string $nombre): int => UnidadDeMedida::query()
            ->where('nombre', $nombre)
            ->firstOrFail()
            ->id;

        $insumos = [
            ['MP-001', 'Tabla de pino finger-joint', TipoArticulo::MateriaPrima, 'Metro cuadrado', 500, 80, 900],
            ['MP-002', 'Tablero de guatambú 18 mm', TipoArticulo::MateriaPrima, 'Unidad', 200, 30, 400],
            ['MP-003', 'Tela de lino para tapizado', TipoArticulo::MateriaPrima, 'Metro cuadrado', 150, 40, 300],
            ['MP-004', 'Espuma de alta densidad 30 kg', TipoArticulo::MateriaPrima, 'Unidad', 120, 20, 250],
            ['IN-001', 'Cola vinílica', TipoArticulo::InsumoConsumible, 'Litro', 80, 20, 200],
            ['IN-002', 'Barniz poliuretánico', TipoArticulo::InsumoConsumible, 'Litro', 60, 15, 150],
            ['IN-003', 'Tornillos surtidos', TipoArticulo::InsumoConsumible, 'Unidad', 5000, 500, 8000],
            ['IN-004', 'Bisagras ocultas', TipoArticulo::InsumoConsumible, 'Unidad', 300, 50, 600],
        ];

        foreach ($insumos as [$sku, $descripcion, $tipo, $unidadNombre, $stock, $minimo, $maximo]) {
            Articulo::query()->firstOrCreate(
                ['codigo_sku' => $sku],
                [
                    'descripcion' => $descripcion,
                    'tipo' => $tipo->value,
                    'origen' => null,
                    'stock_fisico' => $stock,
                    'stock_minimo' => $minimo,
                    'stock_maximo' => $maximo,
                    'estado' => EstadoArticulo::Activo->value,
                    'unidad_de_medida_id' => $unidad($unidadNombre),
                ],
            );
        }

        $productos = [
            ['PT-001', 'Silla Misionera Tapizada Lino'],
            ['PT-002', 'Mesa Paraíso Extensible 1,80 m'],
            ['PT-003', 'Aparador Guatambú 3 Puertas'],
            ['PT-004', 'Banqueta Alta Eucalipto'],
        ];

        foreach ($productos as [$sku, $descripcion]) {
            Articulo::query()->firstOrCreate(
                ['codigo_sku' => $sku],
                [
                    'descripcion' => $descripcion,
                    'tipo' => TipoArticulo::ProductoTerminado->value,
                    'origen' => OrigenArticulo::Fabricado->value,
                    'stock_fisico' => 0,
                    'stock_minimo' => 0,
                    'stock_maximo' => 0,
                    'estado' => EstadoArticulo::Activo->value,
                    'unidad_de_medida_id' => $unidad('Unidad'),
                ],
            );
        }
    }
}
