<?php

namespace Database\Factories;

use App\Modules\Produccion\Models\Maquinaria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Maquinaria>
 */
class MaquinariaFactory extends Factory
{
    /**
     * @var class-string<Maquinaria>
     */
    protected $model = Maquinaria::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->word(),
            'tipo' => 'Lijadora',
            'estado_operativo' => 'Disponible',
            'desgaste_acumulado' => 0,
        ];
    }
}
