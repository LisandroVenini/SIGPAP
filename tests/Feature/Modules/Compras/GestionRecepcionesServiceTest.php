<?php

namespace Tests\Feature\Modules\Compras;

use App\Modules\Compras\Models\Cotizacion;
use App\Modules\Compras\Models\Incidencia;
use App\Modules\Compras\Models\LineaOrdenCompra;
use App\Modules\Compras\Models\OrdenDeCompra;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Services\GestionRecepcionesService;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Usuarios\Models\Usuario;
use Database\Factories\CotizacionFactory;
use Database\Factories\LineaCotizacionFactory;
use Database\Factories\LineaOrdenCompraFactory;
use Database\Factories\OrdenDeCompraFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GestionRecepcionesServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): GestionRecepcionesService
    {
        return app(GestionRecepcionesService::class);
    }

    /**
     * @return array{proveedor: Proveedor, cotizacion: Cotizacion, articulo: Articulo, orden: OrdenDeCompra, linea: LineaOrdenCompra}
     */
    private function contexto(
        string $estado = 'Confirmada',
        ?string $fechaConfirmacion = null,
        ?string $fechaPrometida = null,
        float $cantidadPedida = 10,
    ): array {
        $proveedor = Proveedor::factory()->create(['razon_social' => 'Aceros del Sur']);
        $articulo = Articulo::factory()->create([
            'descripcion' => 'Tabla de pino',
        ]);

        $cotizacion = CotizacionFactory::new()->create([
            'proveedor_id' => $proveedor->id,
            'solicitud_de_disponibilidad_id' => null,
        ]);

        LineaCotizacionFactory::new()->create([
            'cotizacion_id' => $cotizacion->id,
            'articulo_id' => $articulo->id,
            'fecha_prometida' => $fechaPrometida ?? today()->addWeek()->toDateString(),
        ]);

        $orden = OrdenDeCompraFactory::new()->create([
            'proveedor_id' => $proveedor->id,
            'cotizacion_id' => $cotizacion->id,
            'solicitud_de_disponibilidad_id' => null,
            'estado' => $estado,
            'fecha_emision' => today()->subDays(10),
            'fecha_confirmacion' => $fechaConfirmacion,
        ]);

        $linea = LineaOrdenCompraFactory::new()->create([
            'orden_de_compra_id' => $orden->id,
            'articulo_id' => $articulo->id,
            'cantidad' => $cantidadPedida,
            'precio_pactado' => 120.50,
        ]);

        return compact('proveedor', 'cotizacion', 'articulo', 'orden', 'linea');
    }

    public function test_registrar_recepcion_closes_the_order_and_registers_partial_delivery(): void
    {
        // Arrange
        $actor = Usuario::factory()->create();
        $contexto = $this->contexto(
            fechaConfirmacion: today()->subDays(3)->toDateString(),
            fechaPrometida: today()->subDays(1)->toDateString(),
        );

        // Act
        $this->service()->registrarRecepcion(
            $actor,
            $contexto['orden'],
            true,
            [
                ['articulo_id' => $contexto['articulo']->id, 'cantidad' => 6],
            ],
            '127.0.0.1',
        );

        // Assert
        $orden = $contexto['orden']->refresh();
        $this->assertSame('Recibida', $orden->estado);
        $this->assertTrue($orden->entrega_parcial);
        $this->assertDatabaseHas('desempenos_de_proveedor', [
            'proveedor_id' => $contexto['proveedor']->id,
            'orden_de_compra_id' => $orden->id,
            'tiempo_entrega_real' => 3,
            'fecha_registro' => today()->toDateString(),
        ]);
        $this->assertDatabaseHas('incidencias', [
            'orden_de_compra_id' => $orden->id,
            'tipo' => 'Faltante',
        ]);
        $this->assertDatabaseHas('incidencias', [
            'orden_de_compra_id' => $orden->id,
            'tipo' => 'Retraso',
        ]);
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'registrar_recepcion_orden',
            'usuario_id' => $actor->id,
            'auditable_type' => OrdenDeCompra::class,
            'auditable_id' => $orden->id,
        ]);
    }

    public function test_registrar_recepcion_uses_the_emission_date_when_the_order_was_never_confirmed(): void
    {
        // Arrange
        $actor = Usuario::factory()->create();
        $contexto = $this->contexto(
            estado: 'Emitida',
            fechaConfirmacion: null,
        );

        // Act
        $this->service()->registrarRecepcion(
            $actor,
            $contexto['orden'],
            false,
            [
                ['articulo_id' => $contexto['articulo']->id, 'cantidad' => 10],
            ],
        );

        // Assert
        $this->assertDatabaseHas('desempenos_de_proveedor', [
            'orden_de_compra_id' => $contexto['orden']->id,
            'tiempo_entrega_real' => 10,
        ]);
    }

    public function test_registrar_recepcion_creates_no_incidencias_when_the_order_arrives_complete_and_on_time(): void
    {
        // Arrange
        $actor = Usuario::factory()->create();
        $contexto = $this->contexto(
            fechaConfirmacion: today()->toDateString(),
            fechaPrometida: today()->addWeek()->toDateString(),
        );

        // Act
        $this->service()->registrarRecepcion(
            $actor,
            $contexto['orden'],
            false,
            [
                ['articulo_id' => $contexto['articulo']->id, 'cantidad' => 10],
            ],
        );

        // Assert
        $this->assertSame(0, Incidencia::query()->count());
    }

    public function test_registrar_recepcion_rejects_an_order_that_is_not_recepcionable(): void
    {
        // Arrange
        $actor = Usuario::factory()->create();
        $contexto = $this->contexto(estado: 'Recibida');

        // Act / Assert
        try {
            $this->service()->registrarRecepcion(
                $actor,
                $contexto['orden'],
                false,
                [
                    ['articulo_id' => $contexto['articulo']->id, 'cantidad' => 10],
                ],
            );

            $this->fail('A received order must not be received again.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('estado', $exception->errors());
        }

        $this->assertSame('Recibida', $contexto['orden']->refresh()->estado);
        $this->assertDatabaseCount('desempenos_de_proveedor', 0);
    }

    public function test_ordenes_recepcionables_only_returns_emitted_and_confirmed_orders_with_resolved_lines(): void
    {
        // Arrange
        $contexto = $this->contexto(estado: 'Confirmada');
        OrdenDeCompraFactory::new()->create([
            'proveedor_id' => $contexto['proveedor']->id,
            'cotizacion_id' => $contexto['cotizacion']->id,
            'solicitud_de_disponibilidad_id' => null,
            'estado' => 'Recibida',
        ]);

        // Act
        $ordenes = $this->service()->ordenesRecepcionables();

        // Assert
        $this->assertCount(1, $ordenes);
        $this->assertSame($contexto['orden']->numero_oc, $ordenes->first()['numero_oc']);
        $this->assertSame('Aceros del Sur', $ordenes->first()['proveedor']);
        $this->assertSame($contexto['articulo']->codigo_sku, $ordenes->first()['lineas'][0]['articulo']['codigo_sku']);
        $this->assertSame('Tabla de pino', $ordenes->first()['lineas'][0]['articulo']['descripcion']);
    }

    public function test_buscar_por_numero_returns_the_order_data_only_when_it_is_recepcionable(): void
    {
        // Arrange
        $contexto = $this->contexto(estado: 'Emitida');
        $noRecepcionable = $this->contexto(estado: 'Anulada');

        // Act
        $encontrada = $this->service()->buscarPorNumero($contexto['orden']->numero_oc);
        $anulada = $this->service()->buscarPorNumero($noRecepcionable['orden']->numero_oc);
        $inexistente = $this->service()->buscarPorNumero('OC-INEXISTENTE');

        // Assert
        $this->assertNotNull($encontrada);
        $this->assertSame($contexto['orden']->id, $encontrada['id']);
        $this->assertSame($contexto['articulo']->id, $encontrada['lineas'][0]['articulo_id']);
        $this->assertNull($anulada);
        $this->assertNull($inexistente);
    }
}
