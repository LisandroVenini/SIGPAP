<?php

namespace Database\Factories;

use App\Modules\Compras\Models\SolicitudDeCotizacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SolicitudDeCotizacion>
 */
class SolicitudDeCotizacionFactory extends Factory
{
    /**
     * @var class-string<SolicitudDeCotizacion>
     */
    protected $model = SolicitudDeCotizacion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fecha' => now(),
            'fecha_vencimiento' => now()->addWeek(),
            'estado' => 'Borrador',
        ];
    }
}
