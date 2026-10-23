<?php

namespace Database\Factories;

use App\Modules\Produccion\Models\ControlDeCalidad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ControlDeCalidad>
 */
class ControlDeCalidadFactory extends Factory
{
    /**
     * @var class-string<ControlDeCalidad>
     */
    protected $model = ControlDeCalidad::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'orden_de_produccion_id' => OrdenDeProduccionFactory::new(),
            'fecha' => now(),
            'cantidad_aprobada' => 4,
            'cantidad_rechazada' => 1,
            'motivo_rechazo' => null,
        ];
    }
}
