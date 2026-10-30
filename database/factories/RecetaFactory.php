<?php

namespace Database\Factories;

use App\Modules\Produccion\Models\Receta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Receta>
 */
class RecetaFactory extends Factory
{
    /**
     * @var class-string<Receta>
     */
    protected $model = Receta::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'articulo_id' => ArticuloFactory::new(),
        ];
    }
}
