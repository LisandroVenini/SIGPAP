<?php

namespace Database\Seeders;

use App\Modules\Usuarios\Models\ParametroGlobal;
use Illuminate\Database\Seeder;

class ParametroGlobalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $parametros = [
            ['nombre' => 'umbral_tolerancia_precio', 'valor' => '10', 'categoria' => 'Compras'],
            ['nombre' => 'turnos', 'valor' => 'Mañana,Tarde', 'categoria' => 'Producción'],
            ['nombre' => 'feriados', 'valor' => '', 'categoria' => 'Producción'],
        ];

        foreach ($parametros as $parametro) {
            ParametroGlobal::updateOrCreate(
                ['nombre' => $parametro['nombre']],
                ['valor' => $parametro['valor'], 'categoria' => $parametro['categoria']],
            );
        }
    }
}
