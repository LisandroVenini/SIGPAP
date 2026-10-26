<?php

namespace Database\Factories;

use App\Modules\Inventario\Models\MovimientoDeStock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MovimientoDeStock>
 */
class MovimientoDeStockFactory extends Factory
{
    /**
     * @var class-string<MovimientoDeStock>
     */
    protected $model = MovimientoDeStock::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'articulo_id' => ArticuloFactory::new(),
            'orden_de_produccion_id' => null,
            'orden_de_compra_id' => null,
            'tipo' => 'Ingreso',
            'cantidad' => 10,
            'fecha' => now(),
            'motivo' => null,
            'numero_remito' => null,
        ];
    }
}
