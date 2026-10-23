<?php

namespace Database\Factories;

use App\Modules\Produccion\Models\OrdenDeProduccion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrdenDeProduccion>
 */
class OrdenDeProduccionFactory extends Factory
{
    /**
     * @var class-string<OrdenDeProduccion>
     */
    protected $model = OrdenDeProduccion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'articulo_id' => ArticuloFactory::new(),
            'planificacion_de_produccion_id' => PlanificacionDeProduccionFactory::new(),
            'estado' => 'Pendiente',
            'cantidad_requerida' => 10,
            'fecha_entrega' => now()->addWeek(),
            'fecha_inicio' => null,
            'fecha_fin' => null,
            'cantidad_producida' => 0,
            'prioridad_actual' => 1,
        ];
    }
}
