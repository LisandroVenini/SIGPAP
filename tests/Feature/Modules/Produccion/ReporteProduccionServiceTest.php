<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Inventario\Models\Articulo;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Services\GestionOrdenesDeProduccionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReporteProduccionServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): GestionOrdenesDeProduccionService
    {
        return app(GestionOrdenesDeProduccionService::class);
    }

    public function test_reporte_filters_by_fecha_entrega_range_inclusively_and_orders_ascending(): void
    {
        // Arrange
        $antes = OrdenDeProduccion::factory()->create(['fecha_entrega' => '2026-03-04']);
        $primera = OrdenDeProduccion::factory()->create(['fecha_entrega' => '2026-03-05']);
        $segunda = OrdenDeProduccion::factory()->create(['fecha_entrega' => '2026-03-10']);
        $despues = OrdenDeProduccion::factory()->create(['fecha_entrega' => '2026-03-11']);

        // Act
        $filas = $this->service()->reportePorFechaDeEntrega('2026-03-05', '2026-03-10', null);

        // Assert
        $this->assertSame([$primera->id, $segunda->id], array_column($filas, 'orden_id'));
        $this->assertNotContains($antes->id, array_column($filas, 'orden_id'));
        $this->assertNotContains($despues->id, array_column($filas, 'orden_id'));
    }

    public function test_reporte_returns_the_expected_columns_and_resolves_product_and_plan_names(): void
    {
        // Arrange
        $articulo = Articulo::factory()->create(['descripcion' => 'Silla de pino']);
        $orden = OrdenDeProduccion::factory()->create([
            'articulo_id' => $articulo->id,
            'estado' => 'En Curso',
            'cantidad_requerida' => 12,
            'cantidad_producida' => 4,
            'fecha_entrega' => '2026-03-10',
            'fecha_inicio' => '2026-03-06',
            'fecha_fin' => '2026-03-09',
        ]);
        $orden->planificacionDeProduccion->update(['nombre' => 'Plan marzo']);

        // Act
        $filas = $this->service()->reportePorFechaDeEntrega('2026-03-01', '2026-03-31', null);

        // Assert
        $this->assertCount(1, $filas);
        $this->assertSame([
            'orden_id' => $orden->id,
            'producto' => 'Silla de pino',
            'planificacion' => 'Plan marzo',
            'estado' => 'En Curso',
            'cantidad_requerida' => 12.0,
            'cantidad_producida' => 4.0,
            'fecha_entrega' => '2026-03-10',
            'fecha_inicio' => '2026-03-06',
            'fecha_fin' => '2026-03-09',
        ], $filas[0]);
    }

    public function test_reporte_filters_by_estado_when_provided(): void
    {
        // Arrange
        $pendiente = OrdenDeProduccion::factory()->create(['fecha_entrega' => '2026-03-10', 'estado' => 'Pendiente']);
        OrdenDeProduccion::factory()->create(['fecha_entrega' => '2026-03-10', 'estado' => 'En Curso']);

        // Act
        $filas = $this->service()->reportePorFechaDeEntrega('2026-03-01', '2026-03-31', 'Pendiente');

        // Assert
        $this->assertSame([$pendiente->id], array_column($filas, 'orden_id'));
    }

    public function test_reporte_resolves_names_without_n_plus_one_queries(): void
    {
        // Arrange
        OrdenDeProduccion::factory()->count(5)->create(['fecha_entrega' => '2026-03-10']);

        DB::flushQueryLog();
        DB::enableQueryLog();

        // Act
        $this->service()->reportePorFechaDeEntrega('2026-03-01', '2026-03-31', null);

        // Assert
        $consultas = DB::getQueryLog();
        DB::disableQueryLog();

        // Orders, product summaries and their units: constant, never per row.
        $this->assertCount(3, $consultas);
    }
}
