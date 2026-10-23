<?php

namespace Database\Factories;

use App\Modules\Compras\Models\LineaSolicitudCotizacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LineaSolicitudCotizacion>
 */
class LineaSolicitudCotizacionFactory extends Factory
{
    /**
     * @var class-string<LineaSolicitudCotizacion>
     */
    protected $model = LineaSolicitudCotizacion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'solicitud_de_cotizacion_id' => SolicitudDeCotizacionFactory::new(),
            'articulo_id' => ArticuloFactory::new(),
            'cantidad_solicitada' => 5,
        ];
    }
}
