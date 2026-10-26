<?php

namespace Database\Factories;

use App\Modules\Produccion\Models\Etapa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Etapa>
 */
class EtapaFactory extends Factory
{
    /**
     * @var class-string<Etapa>
     */
    protected $model = Etapa::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->word(),
            'descripcion' => null,
        ];
    }
}
