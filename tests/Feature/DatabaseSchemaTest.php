<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_domain_tables_exist(): void
    {
        // Arrange
        $tables = [
            'usuarios', 'roles', 'permissions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions', 'parametros_globales',
            'unidades_de_medida', 'articulos', 'reservas', 'movimientos_de_stock',
            'etapas', 'operarios', 'maquinarias', 'planificaciones_de_produccion', 'ordenes_de_produccion',
            'orden_de_produccion_operario', 'orden_de_produccion_maquinaria', 'asignaciones_de_etapa', 'partes_diarios',
            'controles_de_calidad', 'planes_de_mantenimiento', 'intervenciones_de_mantenimiento', 'registros_de_prioridad',
            'proveedores', 'solicitudes_de_disponibilidad', 'solicitudes_de_cotizacion', 'solicitud_de_cotizacion_proveedor',
            'lineas_de_solicitud_de_cotizacion', 'cotizaciones', 'lineas_de_cotizacion', 'ordenes_de_compra',
            'lineas_de_orden_de_compra', 'incidencias', 'desempenos_de_proveedor',
            'tasas_de_rendimiento', 'escenarios_de_simulacion', 'alertas', 'eventos_de_auditoria',
        ];

        // Act / Assert
        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }
    }

    public function test_cotizaciones_enforces_exactly_one_origin(): void
    {
        // Arrange
        $proveedorId = $this->insertProveedor();
        $articuloId = $this->insertArticulo();
        $disponibilidadId = $this->insertSolicitudDisponibilidad($proveedorId, $articuloId);
        $cotizacionId = $this->insertSolicitudCotizacion();

        // Act / Assert: exactly one origin is accepted
        DB::table('cotizaciones')->insert([
            'solicitud_de_disponibilidad_id' => $disponibilidadId,
            'proveedor_id' => $proveedorId,
            'fecha' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->assertSame(1, DB::table('cotizaciones')->count());

        // Neither origin is rejected
        $this->assertQueryFails(fn () => DB::table('cotizaciones')->insert([
            'proveedor_id' => $proveedorId,
            'fecha' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        // Both origins are rejected
        $this->assertQueryFails(fn () => DB::table('cotizaciones')->insert([
            'solicitud_de_cotizacion_id' => $cotizacionId,
            'solicitud_de_disponibilidad_id' => $disponibilidadId,
            'proveedor_id' => $proveedorId,
            'fecha' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    public function test_ordenes_de_compra_enforces_exactly_one_origin(): void
    {
        // Arrange
        $proveedorId = $this->insertProveedor();
        $articuloId = $this->insertArticulo();
        $disponibilidadId = $this->insertSolicitudDisponibilidad($proveedorId, $articuloId);

        // Act / Assert: exactly one origin is accepted
        DB::table('ordenes_de_compra')->insert([
            'numero_oc' => 'OC-1',
            'proveedor_id' => $proveedorId,
            'solicitud_de_disponibilidad_id' => $disponibilidadId,
            'fecha_emision' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->assertSame(1, DB::table('ordenes_de_compra')->count());

        // Neither origin is rejected
        $this->assertQueryFails(fn () => DB::table('ordenes_de_compra')->insert([
            'numero_oc' => 'OC-2',
            'proveedor_id' => $proveedorId,
            'fecha_emision' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    public function test_restrict_fk_prevents_deleting_a_parent_with_children(): void
    {
        // Arrange
        $articuloId = $this->insertArticulo();
        $planificacionId = $this->insertPlanificacion();
        $this->insertOrden($articuloId, $planificacionId);

        // Act / Assert
        $this->assertQueryFails(fn () => DB::table('planificaciones_de_produccion')
            ->where('id', $planificacionId)
            ->delete());
    }

    public function test_partes_diarios_is_unique_per_order_etapa_and_fecha(): void
    {
        // Arrange
        $articuloId = $this->insertArticulo();
        $planificacionId = $this->insertPlanificacion();
        $ordenId = $this->insertOrden($articuloId, $planificacionId);
        $etapaId = $this->insertEtapa();

        $parte = [
            'orden_de_produccion_id' => $ordenId,
            'etapa_id' => $etapaId,
            'fecha' => now()->toDateString(),
            'cantidad_conformada' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Act
        DB::table('partes_diarios')->insert($parte);

        // Assert
        $this->assertQueryFails(fn () => DB::table('partes_diarios')->insert($parte));
    }

    private function assertQueryFails(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected the database query to fail.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }
    }

    private function insertUnidad(): int
    {
        return DB::table('unidades_de_medida')->insertGetId([
            'nombre' => 'Unidad',
            'factor_conversion' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertArticulo(): int
    {
        $unidadId = $this->insertUnidad();

        return DB::table('articulos')->insertGetId([
            'codigo_sku' => 'SKU-1',
            'descripcion' => 'Artículo',
            'tipo' => 'Materia Prima',
            'unidad_de_medida_id' => $unidadId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertProveedor(): int
    {
        return DB::table('proveedores')->insertGetId([
            'razon_social' => 'Proveedor',
            'cuit' => '20-12345678-9',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertSolicitudDisponibilidad(int $proveedorId, int $articuloId): int
    {
        return DB::table('solicitudes_de_disponibilidad')->insertGetId([
            'proveedor_id' => $proveedorId,
            'articulo_id' => $articuloId,
            'fecha' => now()->toDateString(),
            'cantidad_solicitada' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertSolicitudCotizacion(): int
    {
        return DB::table('solicitudes_de_cotizacion')->insertGetId([
            'fecha' => now()->toDateString(),
            'fecha_vencimiento' => now()->addWeek()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertPlanificacion(): int
    {
        return DB::table('planificaciones_de_produccion')->insertGetId([
            'nombre' => 'Campaña',
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => now()->addMonth()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertOrden(int $articuloId, int $planificacionId): int
    {
        return DB::table('ordenes_de_produccion')->insertGetId([
            'articulo_id' => $articuloId,
            'planificacion_de_produccion_id' => $planificacionId,
            'cantidad_requerida' => 10,
            'fecha_entrega' => now()->addWeek()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertEtapa(): int
    {
        return DB::table('etapas')->insertGetId([
            'nombre' => 'Lijado',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
