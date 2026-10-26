<?php

namespace Database\Factories;

use App\Modules\Compras\Models\OrdenDeCompra;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrdenDeCompra>
 */
class OrdenDeCompraFactory extends Factory
{
    /**
     * @var class-string<OrdenDeCompra>
     */
    protected $model = OrdenDeCompra::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'numero_oc' => fake()->unique()->bothify('OC-####'),
            'proveedor_id' => ProveedorFactory::new(),
            'cotizacion_id' => null,
            'solicitud_de_disponibilidad_id' => SolicitudDeDisponibilidadFactory::new(),
            'estado' => 'Emitida',
            'entrega_parcial' => false,
            'fecha_emision' => now(),
        ];
    }
}
