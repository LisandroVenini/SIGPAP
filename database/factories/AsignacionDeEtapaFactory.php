<?php

namespace Database\Factories;

use App\Modules\Produccion\Models\AsignacionDeEtapa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AsignacionDeEtapa>
 */
class AsignacionDeEtapaFactory extends Factory
{
    /**
     * @var class-string<AsignacionDeEtapa>
     */
    protected $model = AsignacionDeEtapa::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'articulo_id' => ArticuloFactory::new(),
            'etapa_id' => EtapaFactory::new(),
            'tiempo_por_unidad' => 5,
        ];
    }
}
