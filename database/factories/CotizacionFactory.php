<?php

namespace Database\Factories;

use App\Modules\Compras\Models\Cotizacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cotizacion>
 */
class CotizacionFactory extends Factory
{
    /**
     * @var class-string<Cotizacion>
     */
    protected $model = Cotizacion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'solicitud_de_cotizacion_id' => SolicitudDeCotizacionFactory::new(),
            'solicitud_de_disponibilidad_id' => null,
            'proveedor_id' => ProveedorFactory::new(),
            'fecha' => now(),
        ];
    }
}
