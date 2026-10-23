<?php

namespace Database\Factories;

use App\Modules\Inventario\Models\UnidadDeMedida;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnidadDeMedida>
 */
class UnidadDeMedidaFactory extends Factory
{
    /**
     * @var class-string<UnidadDeMedida>
     */
    protected $model = UnidadDeMedida::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->word(),
            'factor_conversion' => 1,
        ];
    }
}
