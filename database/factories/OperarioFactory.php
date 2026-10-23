<?php

namespace Database\Factories;

use App\Modules\Produccion\Models\Operario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Operario>
 */
class OperarioFactory extends Factory
{
    /**
     * @var class-string<Operario>
     */
    protected $model = Operario::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->firstName(),
            'apellido' => fake()->lastName(),
            'especialidad' => 'Pintado',
        ];
    }
}
