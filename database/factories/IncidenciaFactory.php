<?php

namespace Database\Factories;

use App\Modules\Compras\Models\Incidencia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Incidencia>
 */
class IncidenciaFactory extends Factory
{
    /**
     * @var class-string<Incidencia>
     */
    protected $model = Incidencia::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'proveedor_id' => ProveedorFactory::new(),
            'orden_de_compra_id' => null,
            'tipo' => 'Retraso',
            'fecha' => now(),
            'detalle' => 'Demora en la entrega',
        ];
    }
}
