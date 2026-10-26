<?php

namespace Database\Factories;

use App\Modules\Predictivo\Models\EscenarioDeSimulacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EscenarioDeSimulacion>
 */
class EscenarioDeSimulacionFactory extends Factory
{
    /**
     * @var class-string<EscenarioDeSimulacion>
     */
    protected $model = EscenarioDeSimulacion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'articulo_id' => ArticuloFactory::new(),
            'cantidad_ficticia' => 30,
            'fecha_limite' => now()->addWeek(),
            'prioridad' => 1,
            'impacto_proyectado' => 2,
        ];
    }
}
