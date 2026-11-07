<?php

namespace Tests\Feature\Modules\Inventario;

use App\Modules\Compras\Models\OrdenDeCompra;
use App\Modules\Inventario\Enums\TipoMovimientoStock;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Services\GestionIngresosService;
use App\Modules\Usuarios\Models\Usuario;
use Database\Factories\LineaOrdenCompraFactory;
use Database\Factories\OrdenDeCompraFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GestionIngresosServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): GestionIngresosService
    {
        return app(GestionIngresosService::class);
    }

    /**
     * @return array{orden: OrdenDeCompra, articulo: Articulo}
     */
    private function contexto(float $stockInicial = 0, float $cantidadPedida = 5, string $estado = 'Confirmada'): array
    {
        $articulo = Articulo::factory()->create(['stock_fisico' => $stockInicial]);
        $orden = OrdenDeCompraFactory::new()->create(['estado' => $estado]);
        LineaOrdenCompraFactory::new()->create([
            'orden_de_compra_id' => $orden->id,
            'articulo_id' => $articulo->id,
            'cantidad' => $cantidadPedida,
        ]);

        return compact('orden', 'articulo');
    }

    public function test_registrar_increases_stock_and_creates_an_ingreso_movement(): void
    {
        // Arrange
        $actor = Usuario::factory()->create();
        $contexto = $this->contexto(stockInicial: 2);

        // Act
        $resultado = $this->service()->registrar(
            $actor,
            $contexto['orden']->numero_oc,
            'REM-001',
            [
                ['articulo_id' => $contexto['articulo']->id, 'cantidad' => 5],
            ],
            false,
            false,
            '127.0.0.1',
        );

        // Assert
        $this->assertSame($contexto['orden']->numero_oc, $resultado['orden']);
        $this->assertSame(1, $resultado['recibidas']);
        $this->assertSame(7.0, (float) $contexto['articulo']->refresh()->stock_fisico);
        $this->assertDatabaseHas('movimientos_de_stock', [
            'articulo_id' => $contexto['articulo']->id,
            'orden_de_compra_id' => $contexto['orden']->id,
            'tipo' => TipoMovimientoStock::Ingreso->value,
            'cantidad' => 5,
            'numero_remito' => 'REM-001',
            'fecha' => today()->toDateString(),
        ]);
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'registrar_ingreso_mercancia',
            'usuario_id' => $actor->id,
            'auditable_type' => Articulo::class,
            'auditable_id' => $contexto['articulo']->id,
        ]);
        $this->assertSame('Recibida', $contexto['orden']->refresh()->estado);
    }

    public function test_registrar_rejects_an_excedente_without_confirmation(): void
    {
        // Arrange
        $actor = Usuario::factory()->create();
        $contexto = $this->contexto(cantidadPedida: 5);

        // Act / Assert
        try {
            $this->service()->registrar(
                $actor,
                $contexto['orden']->numero_oc,
                'REM-001',
                [
                    ['articulo_id' => $contexto['articulo']->id, 'cantidad' => 8],
                ],
                false,
                false,
            );

            $this->fail('An excedente without confirmation must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('lineas', $exception->errors());
        }

        $this->assertSame(0.0, (float) $contexto['articulo']->refresh()->stock_fisico);
        $this->assertDatabaseCount('movimientos_de_stock', 0);
        $this->assertSame('Confirmada', $contexto['orden']->refresh()->estado);
    }

    public function test_registrar_accepts_an_excedente_when_confirmed(): void
    {
        // Arrange
        $actor = Usuario::factory()->create();
        $contexto = $this->contexto(cantidadPedida: 5);

        // Act
        $this->service()->registrar(
            $actor,
            $contexto['orden']->numero_oc,
            'REM-001',
            [
                ['articulo_id' => $contexto['articulo']->id, 'cantidad' => 8],
            ],
            false,
            true,
        );

        // Assert
        $this->assertSame(8.0, (float) $contexto['articulo']->refresh()->stock_fisico);
        $this->assertDatabaseHas('movimientos_de_stock', [
            'articulo_id' => $contexto['articulo']->id,
            'tipo' => TipoMovimientoStock::Ingreso->value,
            'cantidad' => 8,
        ]);
    }

    public function test_registrar_rejects_an_unknown_order(): void
    {
        // Arrange
        $actor = Usuario::factory()->create();
        $articulo = Articulo::factory()->create();

        // Act / Assert
        try {
            $this->service()->registrar(
                $actor,
                'OC-INEXISTENTE',
                'REM-001',
                [
                    ['articulo_id' => $articulo->id, 'cantidad' => 1],
                ],
                false,
                false,
            );

            $this->fail('An unknown order must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('numero_oc', $exception->errors());
        }

        $this->assertSame(0.0, (float) $articulo->refresh()->stock_fisico);
        $this->assertDatabaseCount('movimientos_de_stock', 0);
    }

    public function test_registrar_rejects_an_order_that_is_not_recepcionable(): void
    {
        // Arrange
        $actor = Usuario::factory()->create();
        $contexto = $this->contexto(estado: 'Anulada');

        // Act / Assert
        try {
            $this->service()->registrar(
                $actor,
                $contexto['orden']->numero_oc,
                'REM-001',
                [
                    ['articulo_id' => $contexto['articulo']->id, 'cantidad' => 5],
                ],
                false,
                false,
            );

            $this->fail('A non-recepcionable order must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('numero_oc', $exception->errors());
        }

        $this->assertSame(0.0, (float) $contexto['articulo']->refresh()->stock_fisico);
    }

    public function test_registrar_marks_the_partial_delivery_flag(): void
    {
        // Arrange
        $actor = Usuario::factory()->create();
        $contexto = $this->contexto(cantidadPedida: 5);

        // Act
        $this->service()->registrar(
            $actor,
            $contexto['orden']->numero_oc,
            'REM-001',
            [
                ['articulo_id' => $contexto['articulo']->id, 'cantidad' => 3],
            ],
            true,
            false,
        );

        // Assert
        $orden = $contexto['orden']->refresh();
        $this->assertTrue($orden->entrega_parcial);
        $this->assertSame('Recibida', $orden->estado);
        $this->assertDatabaseHas('incidencias', [
            'orden_de_compra_id' => $orden->id,
            'tipo' => 'Faltante',
        ]);
        $this->assertSame(3.0, (float) $contexto['articulo']->refresh()->stock_fisico);
    }
}
