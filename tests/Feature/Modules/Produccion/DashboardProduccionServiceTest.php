<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Inventario\Models\Articulo;
use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Services\GestionOrdenesDeProduccionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardProduccionServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): GestionOrdenesDeProduccionService
    {
        return app(GestionOrdenesDeProduccionService::class);
    }

    private function orden(string $estado, string $fechaEntrega, Articulo $articulo): OrdenDeProduccion
    {
        return OrdenDeProduccion::factory()->create([
            'articulo_id' => $articulo->id,
            'estado' => $estado,
            'fecha_entrega' => $fechaEntrega,
        ]);
    }

    public function test_resumen_dashboard_aggregates_active_orders_and_the_next_deliveries(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2026-11-09 10:00:00'));

        $silla = Articulo::factory()->create(['descripcion' => 'Silla de pino']);
        $mesa = Articulo::factory()->create(['descripcion' => 'Mesa de roble']);

        $hoy = $this->orden(EstadoOrdenProduccion::Pendiente->value, '2026-11-09', $silla);
        $manana = $this->orden(EstadoOrdenProduccion::Asignada->value, '2026-11-10', $mesa);
        $enCurso = $this->orden(EstadoOrdenProduccion::EnCurso->value, '2026-11-11', $silla);
        $pasado = $this->orden(EstadoOrdenProduccion::Pendiente->value, '2026-11-12', $mesa);
        $pospuesta = $this->orden(EstadoOrdenProduccion::Pospuesta->value, '2026-11-13', $silla);
        $this->orden(EstadoOrdenProduccion::Pendiente->value, '2026-11-20', $mesa);
        $this->orden(EstadoOrdenProduccion::PendientePorInsumos->value, '2026-11-08', $silla);
        $this->orden(EstadoOrdenProduccion::Cerrada->value, '2026-11-07', $silla);
        $this->orden(EstadoOrdenProduccion::Anulada->value, '2026-11-01', $silla);

        // Act
        $resumen = $this->service()->resumenDashboard();

        // Assert
        $this->assertSame(7, $resumen['activas']);
        $this->assertSame(1, $resumen['en_curso']);
        $this->assertSame(1, $resumen['pendientes_por_insumos']);
        $this->assertSame(1, $resumen['atrasadas']);
        $this->assertSame([
            [
                'orden_id' => $hoy->id,
                'producto' => 'Silla de pino',
                'fecha_entrega' => '2026-11-09',
                'estado' => EstadoOrdenProduccion::Pendiente->value,
            ],
            [
                'orden_id' => $manana->id,
                'producto' => 'Mesa de roble',
                'fecha_entrega' => '2026-11-10',
                'estado' => EstadoOrdenProduccion::Asignada->value,
            ],
            [
                'orden_id' => $enCurso->id,
                'producto' => 'Silla de pino',
                'fecha_entrega' => '2026-11-11',
                'estado' => EstadoOrdenProduccion::EnCurso->value,
            ],
            [
                'orden_id' => $pasado->id,
                'producto' => 'Mesa de roble',
                'fecha_entrega' => '2026-11-12',
                'estado' => EstadoOrdenProduccion::Pendiente->value,
            ],
            [
                'orden_id' => $pospuesta->id,
                'producto' => 'Silla de pino',
                'fecha_entrega' => '2026-11-13',
                'estado' => EstadoOrdenProduccion::Pospuesta->value,
            ],
        ], $resumen['proximas_entregas']);
    }

    public function test_resumen_dashboard_returns_zeros_and_no_deliveries_without_orders(): void
    {
        // Act
        $resumen = $this->service()->resumenDashboard();

        // Assert
        $this->assertSame([
            'activas' => 0,
            'en_curso' => 0,
            'pendientes_por_insumos' => 0,
            'atrasadas' => 0,
            'proximas_entregas' => [],
        ], $resumen);
    }
}
