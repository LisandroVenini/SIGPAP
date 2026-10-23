<?php

namespace Database\Factories;

use App\Modules\Produccion\Models\RegistroPrioridad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegistroPrioridad>
 */
class RegistroPrioridadFactory extends Factory
{
    /**
     * @var class-string<RegistroPrioridad>
     */
    protected $model = RegistroPrioridad::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'orden_de_produccion_id' => OrdenDeProduccionFactory::new(),
            'usuario_id' => UsuarioFactory::new(),
            'prioridad_anterior' => null,
            'prioridad_nueva' => 1,
            'fecha_cambio' => now(),
            'motivo' => 'Cliente urgente',
            'detalle' => null,
        ];
    }
}
