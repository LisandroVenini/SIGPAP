<?php

namespace Database\Factories;

use App\Modules\Produccion\Models\PlanDeMantenimiento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlanDeMantenimiento>
 */
class PlanDeMantenimientoFactory extends Factory
{
    /**
     * @var class-string<PlanDeMantenimiento>
     */
    protected $model = PlanDeMantenimiento::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'maquinaria_id' => MaquinariaFactory::new(),
            'umbral_uso' => 1000,
            'descripcion' => 'Cambio de rodillos',
        ];
    }
}
