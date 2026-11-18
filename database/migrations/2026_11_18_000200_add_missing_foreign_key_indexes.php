<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remaining FK columns that are joined or filtered on but had no index.
     */
    private const INDICES = [
        'role_has_permissions' => [
            'rhp_role_idx' => ['role_id'],
        ],
        'orden_de_produccion_operario' => [
            'opo_operario_idx' => ['operario_id'],
        ],
        'orden_de_produccion_maquinaria' => [
            'opm_maquinaria_idx' => ['maquinaria_id'],
        ],
        'asignaciones_de_etapa' => [
            'ade_etapa_idx' => ['etapa_id'],
        ],
        'partes_diarios' => [
            'parte_etapa_idx' => ['etapa_id'],
            'parte_maquinaria_idx' => ['maquinaria_id'],
        ],
        'controles_de_calidad' => [
            'control_orden_idx' => ['orden_de_produccion_id'],
            'control_parte_idx' => ['parte_diario_id'],
        ],
        'registros_de_prioridad' => [
            'prioridad_orden_idx' => ['orden_de_produccion_id'],
            'prioridad_usuario_idx' => ['usuario_id'],
        ],
        'solicitudes_de_disponibilidad' => [
            'disponibilidad_articulo_idx' => ['articulo_id'],
            'disponibilidad_proveedor_idx' => ['proveedor_id'],
        ],
        'cotizaciones' => [
            'cotizacion_disponibilidad_idx' => ['solicitud_de_disponibilidad_id'],
        ],
        'ordenes_de_compra' => [
            'oc_disponibilidad_idx' => ['solicitud_de_disponibilidad_id'],
        ],
        'tasas_de_rendimiento' => [
            'tasa_etapa_idx' => ['etapa_id'],
        ],
        'escenarios_de_simulacion' => [
            'escenario_articulo_idx' => ['articulo_id'],
        ],
        'componentes_de_receta' => [
            'componente_articulo_idx' => ['articulo_id'],
        ],
    ];

    public function up(): void
    {
        foreach (self::INDICES as $tabla => $indices) {
            Schema::table($tabla, function (Blueprint $table) use ($indices): void {
                foreach ($indices as $nombre => $columnas) {
                    $table->index($columnas, $nombre);
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::INDICES as $tabla => $indices) {
            Schema::table($tabla, function (Blueprint $table) use ($indices): void {
                foreach (array_keys($indices) as $nombre) {
                    $table->dropIndex($nombre);
                }
            });
        }
    }
};
