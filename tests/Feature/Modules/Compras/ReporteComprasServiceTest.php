<?php

namespace Tests\Feature\Modules\Compras;

use App\Modules\Compras\Models\OrdenDeCompra;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Services\GestionOrdenesDeCompraService;
use Database\Factories\LineaOrdenCompraFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteComprasServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): GestionOrdenesDeCompraService
    {
        return app(GestionOrdenesDeCompraService::class);
    }

    public function test_reporte_filters_by_fecha_emision_range_inclusively_and_orders_ascending(): void
    {
        // Arrange
        $proveedor = Proveedor::factory()->create(['razon_social' => 'Aceros del Sur']);

        $ordenA = $this->orden($proveedor, 'OC-0001', '2026-05-05');
        $ordenB = $this->orden($proveedor, 'OC-0002', '2026-05-10');
        $this->orden($proveedor, 'OC-0003', '2026-05-11');
        $this->orden($proveedor, 'OC-0004', '2026-05-04');

        // Act
        $filas = $this->service()->reportePorFechaDeEmision('2026-05-05', '2026-05-10');

        // Assert
        $this->assertSame([$ordenA->numero_oc, $ordenB->numero_oc], array_column($filas, 'numero_oc'));
    }

    public function test_reporte_returns_the_expected_columns_with_line_count_and_total(): void
    {
        // Arrange
        $proveedor = Proveedor::factory()->create(['razon_social' => 'Maderas Norte']);
        $orden = $this->orden($proveedor, 'OC-1000', '2026-05-10', 'Confirmada');

        LineaOrdenCompraFactory::new()->create([
            'orden_de_compra_id' => $orden->id,
            'cantidad' => 2,
            'precio_pactado' => 10,
        ]);
        LineaOrdenCompraFactory::new()->create([
            'orden_de_compra_id' => $orden->id,
            'cantidad' => 1,
            'precio_pactado' => 5.5,
        ]);

        // Act
        $filas = $this->service()->reportePorFechaDeEmision('2026-05-01', '2026-05-31');

        // Assert
        $this->assertCount(1, $filas);
        $this->assertSame([
            'numero_oc' => 'OC-1000',
            'proveedor' => 'Maderas Norte',
            'estado' => 'Confirmada',
            'fecha_emision' => '2026-05-10',
            'lineas' => 2,
            'total' => 25.5,
        ], $filas[0]);
    }

    public function test_reporte_returns_zero_line_count_and_total_for_an_order_without_lines(): void
    {
        // Arrange
        $proveedor = Proveedor::factory()->create();
        $this->orden($proveedor, 'OC-2000', '2026-05-10');

        // Act
        $filas = $this->service()->reportePorFechaDeEmision('2026-05-01', '2026-05-31');

        // Assert
        $this->assertSame(0, $filas[0]['lineas']);
        $this->assertSame(0.0, $filas[0]['total']);
    }

    private function orden(Proveedor $proveedor, string $numero, string $fecha, string $estado = 'Emitida'): OrdenDeCompra
    {
        return OrdenDeCompra::factory()->create([
            'numero_oc' => $numero,
            'proveedor_id' => $proveedor->id,
            'estado' => $estado,
            'fecha_emision' => $fecha,
        ]);
    }
}
