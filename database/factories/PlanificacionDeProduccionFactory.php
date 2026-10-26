<?php

namespace Database\Factories;

use App\Modules\Produccion\Models\PlanificacionDeProduccion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlanificacionDeProduccion>
 */
class PlanificacionDeProduccionFactory extends Factory
{
    /**
     * @var class-string<PlanificacionDeProduccion>
     */
    protected $model = PlanificacionDeProduccion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->word(),
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addMonth(),
            'observaciones' => null,
        ];
    }
}
