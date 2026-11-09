<?php

namespace Tests\Feature\Modules\Compras;

use App\Modules\Compras\Enums\EstadoOrdenDeCompra;
use App\Modules\Compras\Enums\EstadoSolicitudDeCotizacion;
use App\Modules\Compras\Models\OrdenDeCompra;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Models\SolicitudDeCotizacion;
use App\Modules\Compras\Services\GestionOrdenesDeCompraService;
use Database\Factories\SolicitudDeDisponibilidadFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardComprasServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): GestionOrdenesDeCompraService
    {
        return app(GestionOrdenesDeCompraService::class);
    }

    public function test_resumen_dashboard_counts_reception_pending_orders_open_solicitudes_and_active_proveedores(): void
    {
        // Arrange
        $proveedor = Proveedor::factory()->create(['estado' => 'Activo']);
        Proveedor::factory()->create(['estado' => 'Activo']);
        Proveedor::factory()->create(['estado' => 'Inactivo']);

        $solicitudDeDisponibilidad = SolicitudDeDisponibilidadFactory::new()->create([
            'proveedor_id' => $proveedor->id,
        ]);

        OrdenDeCompra::factory()->create([
            'proveedor_id' => $proveedor->id,
            'solicitud_de_disponibilidad_id' => $solicitudDeDisponibilidad->id,
            'estado' => EstadoOrdenDeCompra::Emitida->value,
        ]);
        OrdenDeCompra::factory()->create([
            'proveedor_id' => $proveedor->id,
            'solicitud_de_disponibilidad_id' => $solicitudDeDisponibilidad->id,
            'estado' => EstadoOrdenDeCompra::Confirmada->value,
        ]);
        OrdenDeCompra::factory()->create([
            'proveedor_id' => $proveedor->id,
            'solicitud_de_disponibilidad_id' => $solicitudDeDisponibilidad->id,
            'estado' => EstadoOrdenDeCompra::Recibida->value,
        ]);
        OrdenDeCompra::factory()->create([
            'proveedor_id' => $proveedor->id,
            'solicitud_de_disponibilidad_id' => $solicitudDeDisponibilidad->id,
            'estado' => EstadoOrdenDeCompra::Anulada->value,
        ]);
        OrdenDeCompra::factory()->create([
            'proveedor_id' => $proveedor->id,
            'solicitud_de_disponibilidad_id' => $solicitudDeDisponibilidad->id,
            'estado' => EstadoOrdenDeCompra::Rechazada->value,
        ]);

        SolicitudDeCotizacion::factory()->create(['estado' => EstadoSolicitudDeCotizacion::Enviada->value]);
        SolicitudDeCotizacion::factory()->create(['estado' => EstadoSolicitudDeCotizacion::Enviada->value]);
        SolicitudDeCotizacion::factory()->create(['estado' => EstadoSolicitudDeCotizacion::Borrador->value]);
        SolicitudDeCotizacion::factory()->create(['estado' => EstadoSolicitudDeCotizacion::Adjudicada->value]);

        // Act
        $resumen = $this->service()->resumenDashboard();

        // Assert
        $this->assertSame([
            'pendientes_recepcion' => 2,
            'solicitudes_abiertas' => 2,
            'proveedores_activos' => 2,
        ], $resumen);
    }

    public function test_resumen_dashboard_returns_zeros_without_purchase_data(): void
    {
        // Act
        $resumen = $this->service()->resumenDashboard();

        // Assert
        $this->assertSame([
            'pendientes_recepcion' => 0,
            'solicitudes_abiertas' => 0,
            'proveedores_activos' => 0,
        ], $resumen);
    }
}
