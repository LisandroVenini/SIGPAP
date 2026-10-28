<?php

namespace Database\Seeders;

use App\Modules\Inventario\Models\UnidadDeMedida;
use Illuminate\Database\Seeder;

class UnidadDeMedidaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $unidades = [
            ['nombre' => 'Unidad', 'factor_conversion' => 1],
            ['nombre' => 'Kilogramo', 'factor_conversion' => 1],
            ['nombre' => 'Metro', 'factor_conversion' => 1],
            ['nombre' => 'Metro cuadrado', 'factor_conversion' => 1],
            ['nombre' => 'Litro', 'factor_conversion' => 1],
        ];

        foreach ($unidades as $unidad) {
            UnidadDeMedida::updateOrCreate(
                ['nombre' => $unidad['nombre']],
                ['factor_conversion' => $unidad['factor_conversion']],
            );
        }
    }
}
