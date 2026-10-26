<?php

namespace Database\Factories;

use App\Shared\Alertas\Models\Alerta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alerta>
 */
class AlertaFactory extends Factory
{
    /**
     * @var class-string<Alerta>
     */
    protected $model = Alerta::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tipo' => 'Reposición',
            'fecha' => now(),
            'descripcion' => 'Stock bajo',
            'origen' => 'Inventario',
            'orden_de_produccion_id' => null,
            'articulo_id' => null,
            'maquinaria_id' => null,
        ];
    }
}
