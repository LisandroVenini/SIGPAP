<?php

namespace Database\Factories;

use App\Modules\Produccion\Models\ParteDiario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParteDiario>
 */
class ParteDiarioFactory extends Factory
{
    /**
     * @var class-string<ParteDiario>
     */
    protected $model = ParteDiario::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'orden_de_produccion_id' => OrdenDeProduccionFactory::new(),
            'etapa_id' => EtapaFactory::new(),
            'maquinaria_id' => MaquinariaFactory::new(),
            'fecha' => now(),
            'cantidad_conformada' => 5,
            'observaciones' => null,
        ];
    }
}
