<?php

namespace Database\Factories;

use App\Modules\Produccion\Models\ComponenteDeReceta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComponenteDeReceta>
 */
class ComponenteDeRecetaFactory extends Factory
{
    /**
     * @var class-string<ComponenteDeReceta>
     */
    protected $model = ComponenteDeReceta::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'receta_id' => RecetaFactory::new(),
            'articulo_id' => ArticuloFactory::new(),
            'cantidad' => fake()->randomFloat(2, 0.1, 100),
        ];
    }
}
