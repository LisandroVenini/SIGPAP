<?php

namespace Database\Factories;

use App\Modules\Produccion\Models\IntervencionDeMantenimiento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IntervencionDeMantenimiento>
 */
class IntervencionDeMantenimientoFactory extends Factory
{
    /**
     * @var class-string<IntervencionDeMantenimiento>
     */
    protected $model = IntervencionDeMantenimiento::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'maquinaria_id' => MaquinariaFactory::new(),
            'fecha' => now(),
            'tipo' => 'Preventivo',
            'responsable' => 'Taller externo',
            'observaciones' => null,
        ];
    }
}
