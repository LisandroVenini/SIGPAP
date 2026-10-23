<?php

namespace Database\Factories;

use App\Modules\Inventario\Models\Articulo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Articulo>
 */
class ArticuloFactory extends Factory
{
    /**
     * @var class-string<Articulo>
     */
    protected $model = Articulo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo_sku' => fake()->unique()->bothify('SKU-####'),
            'descripcion' => fake()->sentence(3),
            'tipo' => 'Materia Prima',
            'origen' => null,
            'stock_fisico' => 0,
            'stock_minimo' => 5,
            'stock_maximo' => 100,
            'fecha_vencimiento' => null,
            'estado' => 'Activo',
            'unidad_de_medida_id' => UnidadDeMedidaFactory::new(),
        ];
    }
}
