<?php

namespace Database\Factories;

use App\Modules\Predictivo\Models\TasaDeRendimiento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TasaDeRendimiento>
 */
class TasaDeRendimientoFactory extends Factory
{
    /**
     * @var class-string<TasaDeRendimiento>
     */
    protected $model = TasaDeRendimiento::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'etapa_id' => EtapaFactory::new(),
            'valor' => 1.5,
            'desviacion' => -0.2,
            'fecha_calculo' => now(),
        ];
    }
}
