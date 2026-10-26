<?php

namespace Database\Factories;

use App\Modules\Inventario\Models\Reserva;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reserva>
 */
class ReservaFactory extends Factory
{
    /**
     * @var class-string<Reserva>
     */
    protected $model = Reserva::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'articulo_id' => ArticuloFactory::new(),
            'orden_de_produccion_id' => OrdenDeProduccionFactory::new(),
            'cantidad' => 5,
            'estado' => 'Vigente',
        ];
    }
}
