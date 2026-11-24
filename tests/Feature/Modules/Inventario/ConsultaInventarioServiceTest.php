<?php

namespace Tests\Feature\Modules\Inventario;

use App\Modules\Inventario\Enums\EstadoArticulo;
use App\Modules\Inventario\Enums\EstadoReserva;
use App\Modules\Inventario\Enums\OrigenArticulo;
use App\Modules\Inventario\Enums\TipoArticulo;
use App\Modules\Inventario\Enums\TipoMovimientoStock;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Models\MovimientoDeStock;
use App\Modules\Inventario\Models\Reserva;
use App\Modules\Inventario\Services\ConsultaInventarioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsultaInventarioServiceTest extends TestCase
{
    use RefreshDatabase;

    private function servicio(): ConsultaInventarioService
    {
        return app(ConsultaInventarioService::class);
    }

    private function insumo(array $atributos = []): Articulo
    {
        return Articulo::factory()->create($atributos + [
            'tipo' => TipoArticulo::MateriaPrima->value,
            'origen' => OrigenArticulo::Tercero->value,
            'estado' => EstadoArticulo::Activo->value,
            'stock_fisico' => 100,
            'stock_minimo' => 20,
            'stock_maximo' => 300,
        ]);
    }

    public function test_it_returns_only_active_inputs(): void
    {
        $insumo = $this->insumo();
        Articulo::factory()->create([
            'tipo' => TipoArticulo::ProductoTerminado->value,
            'estado' => EstadoArticulo::Activo->value,
        ]);
        Articulo::factory()->create([
            'tipo' => TipoArticulo::MateriaPrima->value,
            'estado' => EstadoArticulo::Inactivo->value,
        ]);

        $datos = $this->servicio()->datosDeInventario();

        $this->assertCount(1, $datos['articulos']);
        $this->assertSame($insumo->id, $datos['articulos'][0]['id']);
    }

    public function test_it_subtracts_the_current_reservations_from_the_available_stock(): void
    {
        $insumo = $this->insumo(['stock_fisico' => 100]);

        Reserva::factory()->create([
            'articulo_id' => $insumo->id,
            'cantidad' => 30,
            'estado' => EstadoReserva::Vigente->value,
        ]);
        Reserva::factory()->create([
            'articulo_id' => $insumo->id,
            'cantidad' => 15,
            'estado' => EstadoReserva::Liberada->value,
        ]);

        $articulo = $this->servicio()->datosDeInventario()['articulos'][0];

        $this->assertSame(30.0, $articulo['comprometido']);
        $this->assertSame(70.0, $articulo['disponible']);
    }

    public function test_it_computes_the_daily_consumption_from_the_movements(): void
    {
        $insumo = $this->insumo();

        MovimientoDeStock::query()->create([
            'articulo_id' => $insumo->id,
            'tipo' => TipoMovimientoStock::Consumo->value,
            'cantidad' => 900,
            'fecha' => now()->subDays(10)->toDateString(),
        ]);
        MovimientoDeStock::query()->create([
            'articulo_id' => $insumo->id,
            'tipo' => TipoMovimientoStock::Ingreso->value,
            'cantidad' => 500,
            'fecha' => now()->subDays(5)->toDateString(),
        ]);

        $articulo = $this->servicio()->datosDeInventario(90)['articulos'][0];

        // 900 consumed over a 90-day window.
        $this->assertSame(10.0, $articulo['consumo_diario']);
    }

    public function test_it_ignores_consumption_outside_the_window(): void
    {
        $insumo = $this->insumo();

        MovimientoDeStock::query()->create([
            'articulo_id' => $insumo->id,
            'tipo' => TipoMovimientoStock::Consumo->value,
            'cantidad' => 900,
            'fecha' => now()->subDays(120)->toDateString(),
        ]);

        $articulo = $this->servicio()->datosDeInventario(90)['articulos'][0];

        $this->assertSame(0.0, $articulo['consumo_diario']);
    }
}
