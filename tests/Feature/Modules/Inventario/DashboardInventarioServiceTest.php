<?php

namespace Tests\Feature\Modules\Inventario;

use App\Modules\Inventario\Enums\EstadoArticulo;
use App\Modules\Inventario\Enums\EstadoReserva;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Models\Reserva;
use App\Modules\Inventario\Services\GestionArticulosService;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardInventarioServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): GestionArticulosService
    {
        return app(GestionArticulosService::class);
    }

    public function test_resumen_dashboard_counts_active_low_stock_articles_and_committed_stock(): void
    {
        // Arrange
        $bajoMinimo = Articulo::factory()->create([
            'estado' => EstadoArticulo::Activo->value,
            'stock_fisico' => 3,
            'stock_minimo' => 5,
        ]);
        Articulo::factory()->create([
            'estado' => EstadoArticulo::Activo->value,
            'stock_fisico' => 5,
            'stock_minimo' => 5,
        ]);
        Articulo::factory()->create([
            'estado' => EstadoArticulo::Activo->value,
            'stock_fisico' => 0,
            'stock_minimo' => null,
        ]);
        Articulo::factory()->create([
            'estado' => EstadoArticulo::Inactivo->value,
            'stock_fisico' => 0,
            'stock_minimo' => 10,
        ]);

        $orden = OrdenDeProduccion::factory()->create([
            'articulo_id' => $bajoMinimo->id,
        ]);

        Reserva::factory()->create([
            'articulo_id' => $bajoMinimo->id,
            'orden_de_produccion_id' => $orden->id,
            'cantidad' => 2.5,
            'estado' => EstadoReserva::Vigente->value,
        ]);
        Reserva::factory()->create([
            'articulo_id' => $bajoMinimo->id,
            'orden_de_produccion_id' => $orden->id,
            'cantidad' => 1.5,
            'estado' => EstadoReserva::Vigente->value,
        ]);
        Reserva::factory()->create([
            'articulo_id' => $bajoMinimo->id,
            'orden_de_produccion_id' => $orden->id,
            'cantidad' => 10,
            'estado' => EstadoReserva::Consumida->value,
        ]);
        Reserva::factory()->create([
            'articulo_id' => $bajoMinimo->id,
            'orden_de_produccion_id' => $orden->id,
            'cantidad' => 3,
            'estado' => EstadoReserva::Liberada->value,
        ]);

        // Act
        $resumen = $this->service()->resumenDashboard();

        // Assert
        $this->assertSame([
            'activos' => 3,
            'bajo_minimo' => 1,
            'comprometido' => 4.0,
        ], $resumen);
    }

    public function test_resumen_dashboard_returns_zeros_without_articles_or_reservations(): void
    {
        // Act
        $resumen = $this->service()->resumenDashboard();

        // Assert
        $this->assertSame([
            'activos' => 0,
            'bajo_minimo' => 0,
            'comprometido' => 0.0,
        ], $resumen);
    }
}
