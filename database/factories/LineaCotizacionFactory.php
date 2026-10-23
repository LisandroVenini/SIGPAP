<?php

namespace Database\Factories;

use App\Modules\Compras\Models\LineaCotizacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LineaCotizacion>
 */
class LineaCotizacionFactory extends Factory
{
    /**
     * @var class-string<LineaCotizacion>
     */
    protected $model = LineaCotizacion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cotizacion_id' => CotizacionFactory::new(),
            'articulo_id' => ArticuloFactory::new(),
            'precio_unitario' => 10,
            'cantidad_ofertada' => 2,
            'fecha_prometida' => now()->addWeek(),
            'adjudicada' => false,
        ];
    }
}
