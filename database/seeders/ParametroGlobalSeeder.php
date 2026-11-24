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
            ['nombre' => 'prod_calendario_laboral', 'valor' => '{"1":[["08:00","12:00"],["16:00","20:00"]],"2":[["08:00","12:00"],["16:00","20:00"]],"3":[["08:00","12:00"],["16:00","20:00"]],"4":[["08:00","12:00"],["16:00","20:00"]],"5":[["08:00","12:00"],["16:00","20:00"]],"6":[["08:00","12:00"]],"7":[]}', 'categoria' => 'Producción'],
            ['nombre' => 'prod_feriados_tipos_bloqueantes', 'valor' => '["inamovible","trasladable","especial","turistico"]', 'categoria' => 'Producción'],
            ['nombre' => 'prod_tolerancia_desvio_dias', 'valor' => '2', 'categoria' => 'Producción'],
            ['nombre' => 'prod_umbral_calidad', 'valor' => '95', 'categoria' => 'Producción'],
            ['nombre' => 'compras_vigencia_cotizacion_dias', 'valor' => '7', 'categoria' => 'Compras'],
            ['nombre' => 'compras_minimo_cotizaciones', 'valor' => '3', 'categoria' => 'Compras'],
            ['nombre' => 'compras_proveedores_por_licitacion', 'valor' => '3', 'categoria' => 'Compras'],
            ['nombre' => 'compras_umbral_aprobacion_gerente', 'valor' => '500000', 'categoria' => 'Compras'],
            ['nombre' => 'compras_ranking_peso_perfecta', 'valor' => '1', 'categoria' => 'Compras'],
            ['nombre' => 'compras_ranking_peso_retraso', 'valor' => '0.6', 'categoria' => 'Compras'],
            ['nombre' => 'compras_ranking_peso_faltante', 'valor' => '0.3', 'categoria' => 'Compras'],
            ['nombre' => 'compras_ranking_peso_rotura', 'valor' => '0', 'categoria' => 'Compras'],
            ['nombre' => 'compras_ranking_ventana_meses', 'valor' => '12', 'categoria' => 'Compras'],
            ['nombre' => 'compras_multicriterio_peso_precio', 'valor' => '0.4', 'categoria' => 'Compras'],
            ['nombre' => 'compras_multicriterio_peso_entrega', 'valor' => '0.3', 'categoria' => 'Compras'],
            ['nombre' => 'compras_multicriterio_peso_confiabilidad', 'valor' => '0.3', 'categoria' => 'Compras'],
            ['nombre' => 'compras_multicriterio_confiabilidad_sin_historial', 'valor' => '50', 'categoria' => 'Compras'],
            ['nombre' => 'compras_multicriterio_penalizar_cobertura', 'valor' => '1', 'categoria' => 'Compras'],
            ['nombre' => 'predictivo_intervalo_recalculo_min', 'valor' => '30', 'categoria' => 'Motor Predictivo'],
            ['nombre' => 'predictivo_reentrenamiento', 'valor' => 'domingo_0200', 'categoria' => 'Motor Predictivo'],
            ['nombre' => 'predictivo_ventana_entrenamiento_meses', 'valor' => '12', 'categoria' => 'Motor Predictivo'],
            ['nombre' => 'predictivo_confianza_minima', 'valor' => '75', 'categoria' => 'Motor Predictivo'],
            ['nombre' => 'predictivo_dias_aviso_riesgo', 'valor' => '7', 'categoria' => 'Motor Predictivo'],
            ['nombre' => 'predictivo_licitacion_automatica', 'valor' => '0', 'categoria' => 'Motor Predictivo'],
            ['nombre' => 'seguridad_expiracion_sesion_min', 'valor' => '30', 'categoria' => 'Seguridad y Sesión'],
            ['nombre' => 'seguridad_intentos_fallidos', 'valor' => '5', 'categoria' => 'Seguridad y Sesión'],
        ];

        // firstOrCreate: a re-run must add missing parameters without overwriting
        // the values an administrator already customised in the configuration screen.
        foreach ($parametros as $parametro) {
            ParametroGlobal::firstOrCreate(
                ['nombre' => $parametro['nombre']],
                ['valor' => $parametro['valor'], 'categoria' => $parametro['categoria']],
            );
        }
    }
}
