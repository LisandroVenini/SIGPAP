<?php

namespace Database\Factories;

use App\Modules\Compras\Models\LineaOrdenCompra;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LineaOrdenCompra>
 */
class LineaOrdenCompraFactory extends Factory
{
    /**
     * @var class-string<LineaOrdenCompra>
     */
    protected $model = LineaOrdenCompra::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'orden_de_compra_id' => OrdenDeCompraFactory::new(),
            'articulo_id' => ArticuloFactory::new(),
            'cantidad' => 5,
            'precio_pactado' => 10,
        ];
    }
}
