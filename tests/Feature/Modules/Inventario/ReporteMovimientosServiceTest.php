<?php

namespace Tests\Feature\Modules\Inventario;

use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Services\ConsultaMovimientosService;
use Database\Factories\MovimientoDeStockFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteMovimientosServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): ConsultaMovimientosService
    {
        return app(ConsultaMovimientosService::class);
    }

    public function test_reporte_filters_by_fecha_range_inclusively_and_orders_ascending(): void
    {
        // Arrange
        $articulo = Articulo::factory()->create();

        MovimientoDeStockFactory::new()->create(['articulo_id' => $articulo->id, 'fecha' => '2026-04-04', 'numero_remito' => 'REM-ANTES']);
        MovimientoDeStockFactory::new()->create(['articulo_id' => $articulo->id, 'fecha' => '2026-04-05', 'numero_remito' => 'REM-1']);
        MovimientoDeStockFactory::new()->create(['articulo_id' => $articulo->id, 'fecha' => '2026-04-10', 'numero_remito' => 'REM-2']);
        MovimientoDeStockFactory::new()->create(['articulo_id' => $articulo->id, 'fecha' => '2026-04-11', 'numero_remito' => 'REM-DESPUES']);

        // Act
        $filas = $this->service()->reportePorFecha('2026-04-05', '2026-04-10');

        // Assert
        $this->assertSame(['2026-04-05', '2026-04-10'], array_column($filas, 'fecha'));
        $this->assertSame(['REM-1', 'REM-2'], array_column($filas, 'numero_remito'));
    }

    public function test_reporte_returns_the_expected_columns_with_sku_and_descripcion(): void
    {
        // Arrange
        $articulo = Articulo::factory()->create([
            'codigo_sku' => 'MP-001',
            'descripcion' => 'Tabla de pino',
        ]);

        MovimientoDeStockFactory::new()->create([
            'articulo_id' => $articulo->id,
            'fecha' => '2026-04-10',
            'tipo' => 'Ingreso',
            'cantidad' => 12.5,
            'numero_remito' => 'REM-0001',
            'motivo' => 'Compra a proveedor',
        ]);

        // Act
        $filas = $this->service()->reportePorFecha('2026-04-01', '2026-04-30');

        // Assert
        $this->assertCount(1, $filas);
        $this->assertSame([
            'fecha' => '2026-04-10',
            'articulo' => 'MP-001 - Tabla de pino',
            'tipo' => 'Ingreso',
            'cantidad' => 12.5,
            'numero_remito' => 'REM-0001',
            'motivo' => 'Compra a proveedor',
        ], $filas[0]);
    }

    public function test_reporte_orders_movements_of_the_same_day_by_id_ascending(): void
    {
        // Arrange
        $articulo = Articulo::factory()->create();

        $primero = MovimientoDeStockFactory::new()->create(['articulo_id' => $articulo->id, 'fecha' => '2026-04-10', 'numero_remito' => 'REM-1']);
        $segundo = MovimientoDeStockFactory::new()->create(['articulo_id' => $articulo->id, 'fecha' => '2026-04-10', 'numero_remito' => 'REM-2']);

        // Act
        $filas = $this->service()->reportePorFecha('2026-04-01', '2026-04-30');

        // Assert
        $this->assertSame(['REM-1', 'REM-2'], array_column($filas, 'numero_remito'));
        $this->assertSame($primero->id, $segundo->id - 1);
    }
}
