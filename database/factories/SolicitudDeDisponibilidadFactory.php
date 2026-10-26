<?php

namespace Database\Factories;

use App\Modules\Compras\Models\SolicitudDeDisponibilidad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SolicitudDeDisponibilidad>
 */
class SolicitudDeDisponibilidadFactory extends Factory
{
    /**
     * @var class-string<SolicitudDeDisponibilidad>
     */
    protected $model = SolicitudDeDisponibilidad::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'proveedor_id' => ProveedorFactory::new(),
            'articulo_id' => ArticuloFactory::new(),
            'fecha' => now(),
            'cantidad_solicitada' => 10,
            'estado' => 'Enviada',
            'token' => null,
        ];
    }
}
