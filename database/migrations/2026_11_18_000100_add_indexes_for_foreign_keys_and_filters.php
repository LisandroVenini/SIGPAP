<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PostgreSQL creates the FK constraint but not an index on the referencing column.
     * These are the columns the application filters, joins and aggregates on.
     */
    private const INDICES = [
        'movimientos_de_stock' => [
            'mov_articulo_fecha_idx' => ['articulo_id', 'fecha'],
            'mov_oc_remito_idx' => ['orden_de_compra_id', 'numero_remito'],
            'mov_orden_prod_idx' => ['orden_de_produccion_id'],
            'mov_fecha_idx' => ['fecha'],
        ],
        'reservas' => [
            'reservas_articulo_estado_idx' => ['articulo_id', 'estado'],
            'reservas_orden_prod_idx' => ['orden_de_produccion_id'],
        ],
        'lineas_de_orden_de_compra' => [
            'loc_orden_idx' => ['orden_de_compra_id'],
            'loc_articulo_idx' => ['articulo_id'],
        ],
        'lineas_de_cotizacion' => [
            'lc_cotizacion_idx' => ['cotizacion_id'],
            'lc_articulo_idx' => ['articulo_id'],
        ],
        'lineas_de_solicitud_de_cotizacion' => [
            'lsc_solicitud_idx' => ['solicitud_de_cotizacion_id'],
            'lsc_articulo_idx' => ['articulo_id'],
        ],
        'eventos_de_auditoria' => [
            'aud_fecha_idx' => ['fecha_hora'],
            'aud_usuario_idx' => ['usuario_id'],
            'aud_accion_idx' => ['accion'],
        ],
        'desempenos_de_proveedor' => [
            'desempeno_prov_idx' => ['proveedor_id'],
            'desempeno_oc_idx' => ['orden_de_compra_id'],
            'desempeno_fecha_idx' => ['fecha_registro'],
        ],
        'incidencias' => [
            'incidencia_oc_idx' => ['orden_de_compra_id'],
            'incidencia_prov_idx' => ['proveedor_id'],
            'incidencia_tipo_idx' => ['tipo'],
        ],
        'ordenes_de_compra' => [
            'oc_proveedor_idx' => ['proveedor_id'],
            'oc_cotizacion_idx' => ['cotizacion_id'],
            'oc_estado_idx' => ['estado'],
            'oc_fecha_emision_idx' => ['fecha_emision'],
        ],
        'solicitudes_de_cotizacion' => [
            'solicitud_estado_idx' => ['estado'],
            'solicitud_vencimiento_idx' => ['fecha_vencimiento'],
        ],
        'cotizaciones' => [
            'cotizacion_solicitud_idx' => ['solicitud_de_cotizacion_id'],
            'cotizacion_proveedor_idx' => ['proveedor_id'],
        ],
        'solicitud_de_cotizacion_proveedor' => [
            'scp_solicitud_idx' => ['solicitud_de_cotizacion_id'],
            'scp_proveedor_idx' => ['proveedor_id'],
        ],
        'articulos' => [
            'articulo_estado_idx' => ['estado'],
            'articulo_tipo_idx' => ['tipo'],
            'articulo_unidad_idx' => ['unidad_de_medida_id'],
        ],
        'ordenes_de_produccion' => [
            'op_estado_idx' => ['estado'],
            'op_entrega_idx' => ['fecha_entrega'],
            'op_planificacion_idx' => ['planificacion_de_produccion_id'],
            'op_articulo_idx' => ['articulo_id'],
        ],
        'intervenciones_de_mantenimiento' => [
            'intervencion_maq_fecha_idx' => ['maquinaria_id', 'fecha'],
        ],
        'maquinarias' => [
            'maquinaria_estado_idx' => ['estado_operativo'],
        ],
        'alertas' => [
            'alerta_articulo_idx' => ['articulo_id'],
            'alerta_orden_prod_idx' => ['orden_de_produccion_id'],
            'alerta_maquinaria_idx' => ['maquinaria_id'],
        ],
        'proveedores' => [
            'proveedor_estado_idx' => ['estado'],
        ],
        'usuarios' => [
            'usuarios_estado_idx' => ['estado'],
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
