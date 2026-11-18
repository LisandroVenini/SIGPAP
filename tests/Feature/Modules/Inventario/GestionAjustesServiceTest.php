<?php

namespace Tests\Feature\Modules\Inventario;

use App\Modules\Inventario\Enums\EstadoReserva;
use App\Modules\Inventario\Enums\MotivoAjuste;
use App\Modules\Inventario\Enums\TipoMovimientoStock;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Models\Reserva;
use App\Modules\Inventario\Services\GestionAjustesService;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GestionAjustesServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): GestionAjustesService
    {
        return app(GestionAjustesService::class);
    }

    public function test_ajustar_sobrescribe_stock_y_registra_movimiento_y_auditoria(): void
    {
        // Arrange
        $actor = Usuario::factory()->create();
        $articulo = Articulo::factory()->create();
        $articulo->stock_fisico = 10;
        $articulo->save();

        // Act
        $this->service()->ajustar($actor, $articulo, 7, MotivoAjuste::Merma->value, '127.0.0.1');

        // Assert
        $this->assertSame(7.0, (float) $articulo->refresh()->stock_fisico);
        $this->assertDatabaseHas('movimientos_de_stock', [
            'articulo_id' => $articulo->id,
            'tipo' => TipoMovimientoStock::Ajuste->value,
            'cantidad' => -3,
            'motivo' => MotivoAjuste::Merma->value,
        ]);
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'usuario_id' => $actor->id,
            'accion' => 'ajustar_inventario',
        ]);
    }

    public function test_ajustar_rechaza_stock_negativo(): void
    {
        // Arrange
        $actor = Usuario::factory()->create();
        $articulo = Articulo::factory()->create();

        // Act / Assert
        $this->expectException(ValidationException::class);
        $this->service()->ajustar($actor, $articulo, -1, MotivoAjuste::Rotura->value);
    }

    public function test_ajustar_rechaza_cuando_no_hay_diferencia(): void
    {
        // Arrange
        $actor = Usuario::factory()->create();
        $articulo = Articulo::factory()->create();
        $articulo->stock_fisico = 5;
        $articulo->save();

        // Act / Assert
        $this->expectException(ValidationException::class);
        $this->service()->ajustar($actor, $articulo, 5, MotivoAjuste::Auditoria->value);
    }

    public function test_ajustar_rechaza_un_stock_por_debajo_de_lo_reservado(): void
    {
        // Arrange
        $actor = Usuario::factory()->create();
        $articulo = Articulo::factory()->create();
        $articulo->stock_fisico = 10;
        $articulo->save();
        Reserva::factory()->create([
            'articulo_id' => $articulo->id,
            'cantidad' => 8,
            'estado' => EstadoReserva::Vigente->value,
        ]);

        // Act / Assert
        try {
            $this->service()->ajustar($actor, $articulo, 5, MotivoAjuste::Merma->value);

            $this->fail('An adjustment below the reserved quantity should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('nuevo_stock', $exception->errors());
        }

        $this->assertSame(10.0, (float) $articulo->refresh()->stock_fisico);
        $this->assertDatabaseCount('movimientos_de_stock', 0);
    }
}
