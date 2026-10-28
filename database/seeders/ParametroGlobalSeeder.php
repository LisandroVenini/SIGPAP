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
            ['nombre' => 'prod_turno_manana_inicio', 'valor' => '06:00', 'categoria' => 'Producción'],
            ['nombre' => 'prod_turno_manana_fin', 'valor' => '14:00', 'categoria' => 'Producción'],
            ['nombre' => 'prod_turno_tarde_inicio', 'valor' => '14:00', 'categoria' => 'Producción'],
            ['nombre' => 'prod_turno_tarde_fin', 'valor' => '22:00', 'categoria' => 'Producción'],
            ['nombre' => 'prod_tolerancia_desvio_dias', 'valor' => '2', 'categoria' => 'Producción'],
            ['nombre' => 'prod_umbral_calidad', 'valor' => '95', 'categoria' => 'Producción'],
            ['nombre' => 'compras_vigencia_cotizacion_dias', 'valor' => '7', 'categoria' => 'Compras'],
            ['nombre' => 'compras_minimo_cotizaciones', 'valor' => '3', 'categoria' => 'Compras'],
            ['nombre' => 'compras_umbral_aprobacion_gerente', 'valor' => '500000', 'categoria' => 'Compras'],
            ['nombre' => 'predictivo_intervalo_recalculo_min', 'valor' => '30', 'categoria' => 'Motor Predictivo'],
            ['nombre' => 'predictivo_reentrenamiento', 'valor' => 'domingo_0200', 'categoria' => 'Motor Predictivo'],
            ['nombre' => 'predictivo_ventana_entrenamiento_meses', 'valor' => '12', 'categoria' => 'Motor Predictivo'],
            ['nombre' => 'predictivo_confianza_minima', 'valor' => '75', 'categoria' => 'Motor Predictivo'],
            ['nombre' => 'seguridad_expiracion_sesion_min', 'valor' => '30', 'categoria' => 'Seguridad y Sesión'],
            ['nombre' => 'seguridad_intentos_fallidos', 'valor' => '5', 'categoria' => 'Seguridad y Sesión'],
        ];

        foreach ($parametros as $parametro) {
            ParametroGlobal::updateOrCreate(
                ['nombre' => $parametro['nombre']],
                ['valor' => $parametro['valor'], 'categoria' => $parametro['categoria']],
            );
        }
    }
}
