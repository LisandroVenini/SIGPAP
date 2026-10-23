<?php

namespace Database\Factories;

use App\Modules\Usuarios\Models\ParametroGlobal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParametroGlobal>
 */
class ParametroGlobalFactory extends Factory
{
    /**
     * @var class-string<ParametroGlobal>
     */
    protected $model = ParametroGlobal::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->word(),
            'valor' => fake()->word(),
            'categoria' => 'Producción',
        ];
    }
}
