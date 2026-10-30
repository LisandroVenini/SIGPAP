<?php

namespace Tests\Feature\Modules\Inventario;

use App\Modules\Inventario\Enums\EstadoReserva;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Models\Reserva;
use App\Modules\Inventario\Services\GestionReservasService;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GestionReservasServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): GestionReservasService
    {
        return app(GestionReservasService::class);
    }

    private function articulo(float $stock): Articulo
    {
        return Articulo::factory()->create(['stock_fisico' => $stock]);
    }

    private function orden(): OrdenDeProduccion
    {
        return OrdenDeProduccion::factory()->create();
    }

    public function test_available_stock_discounts_only_current_reservations(): void
    {
        // Arrange
        $articulo = $this->articulo(10);
        Reserva::factory()->create(['articulo_id' => $articulo->id, 'cantidad' => 3]);
        Reserva::factory()->create([
            'articulo_id' => $articulo->id,
            'cantidad' => 4,
            'estado' => EstadoReserva::Liberada->value,
        ]);
        Reserva::factory()->create([
            'articulo_id' => $articulo->id,
            'cantidad' => 2,
            'estado' => EstadoReserva::Consumida->value,
        ]);

        // Act
        $disponibles = $this->service()->disponibles([$articulo->id]);

        // Assert
        $this->assertSame(7.0, $disponibles[$articulo->id]);
    }

    public function test_available_stock_is_never_negative(): void
    {
        // Arrange
        $articulo = $this->articulo(5);
        Reserva::factory()->create(['articulo_id' => $articulo->id, 'cantidad' => 8]);

        // Act
        $disponibles = $this->service()->disponibles([$articulo->id]);

        // Assert
        $this->assertSame(0.0, $disponibles[$articulo->id]);
    }

    public function test_reserves_every_quantity_when_stock_is_enough(): void
    {
        // Arrange
        $orden = $this->orden();
        $tablon = $this->articulo(10);
        $cola = $this->articulo(2);

        // Act
        $resultado = $this->service()->reservar($orden->id, [$tablon->id => 6.0, $cola->id => 1.5]);

        // Assert
        $this->assertTrue($resultado['cubierto']);
        $this->assertSame([], $resultado['faltantes']);
        $this->assertDatabaseHas('reservas', [
            'orden_de_produccion_id' => $orden->id,
            'articulo_id' => $tablon->id,
            'estado' => EstadoReserva::Vigente->value,
        ]);
        $this->assertDatabaseCount('reservas', 2);
    }

    public function test_reserves_nothing_when_any_insumo_is_short(): void
    {
        // Arrange
        $orden = $this->orden();
        $tablon = $this->articulo(10);
        $cola = $this->articulo(1);

        // Act
        $resultado = $this->service()->reservar($orden->id, [$tablon->id => 6.0, $cola->id => 2.0]);

        // Assert
        $this->assertFalse($resultado['cubierto']);
        $this->assertSame([$cola->id => 1.0], $resultado['faltantes']);
        $this->assertDatabaseCount('reservas', 0);
    }

    public function test_respects_reservations_from_other_orders(): void
    {
        // Arrange
        $articulo = $this->articulo(10);
        $otraOrden = $this->orden();
        $orden = $this->orden();
        Reserva::factory()->create([
            'articulo_id' => $articulo->id,
            'orden_de_produccion_id' => $otraOrden->id,
            'cantidad' => 8,
        ]);

        // Act
        $resultado = $this->service()->reservar($orden->id, [$articulo->id => 3.0]);

        // Assert
        $this->assertFalse($resultado['cubierto']);
        $this->assertSame([$articulo->id => 1.0], $resultado['faltantes']);
        $this->assertDatabaseMissing('reservas', ['orden_de_produccion_id' => $orden->id]);
    }

    public function test_releasing_marks_current_reservations_as_released(): void
    {
        // Arrange
        $orden = $this->orden();
        $articulo = $this->articulo(10);
        Reserva::factory()->create([
            'articulo_id' => $articulo->id,
            'orden_de_produccion_id' => $orden->id,
            'cantidad' => 4,
        ]);

        // Act
        $this->service()->liberar($orden->id);

        // Assert
        $this->assertSame(0, Reserva::query()->where('estado', EstadoReserva::Vigente->value)->count());
        $this->assertSame(10.0, $this->service()->disponibles([$articulo->id])[$articulo->id]);
    }

    public function test_replacing_adjusts_the_quantities(): void
    {
        // Arrange
        $orden = $this->orden();
        $articulo = $this->articulo(10);
        $this->service()->reservar($orden->id, [$articulo->id => 4.0]);

        // Act
        $resultado = $this->service()->reemplazar($orden->id, [$articulo->id => 7.0]);

        // Assert
        $this->assertTrue($resultado['cubierto']);
        $this->assertSame(1, Reserva::query()->where('estado', EstadoReserva::Vigente->value)->count());
        $this->assertSame(3.0, $this->service()->disponibles([$articulo->id])[$articulo->id]);
    }

    public function test_replacing_without_stock_leaves_the_order_without_current_reservations(): void
    {
        // Arrange
        $orden = $this->orden();
        $articulo = $this->articulo(10);
        $this->service()->reservar($orden->id, [$articulo->id => 4.0]);

        // Act
        $resultado = $this->service()->reemplazar($orden->id, [$articulo->id => 20.0]);

        // Assert
        $this->assertFalse($resultado['cubierto']);
        $this->assertSame(0, Reserva::query()
            ->where('orden_de_produccion_id', $orden->id)
            ->where('estado', EstadoReserva::Vigente->value)
            ->count());
        $this->assertSame(10.0, $this->service()->disponibles([$articulo->id])[$articulo->id]);
    }

    public function test_rejects_non_positive_quantities(): void
    {
        // Arrange
        $orden = $this->orden();
        $articulo = $this->articulo(10);

        // Act / Assert
        $this->expectException(ValidationException::class);

        $this->service()->reservar($orden->id, [$articulo->id => 0.0]);
    }
}
