<?php

namespace Tests\Feature\Modules\Compras;

use App\Modules\Compras\Mail\OrdenDeCompraMail;
use App\Modules\Compras\Models\Cotizacion;
use App\Modules\Compras\Models\LineaCotizacion;
use App\Modules\Compras\Models\OrdenDeCompra;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Models\SolicitudDeCotizacion;
use App\Modules\Compras\Services\GestionOrdenesDeCompraService;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Factories\CotizacionFactory;
use Database\Factories\LineaCotizacionFactory;
use Database\Factories\LineaOrdenCompraFactory;
use Database\Factories\OrdenDeCompraFactory;
use Database\Factories\SolicitudDeCotizacionFactory;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GestionOrdenesDeCompraServiceTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole(Rol::EncargadoCompras->value);

        return $usuario;
    }

    private function service(): GestionOrdenesDeCompraService
    {
        return app(GestionOrdenesDeCompraService::class);
    }

    /**
     * @return array{proveedor: Proveedor, solicitud: SolicitudDeCotizacion, articulo: Articulo, cotizacion: Cotizacion, linea: LineaCotizacion}
     */
    private function contexto(string $estadoSolicitud = 'Enviada'): array
    {
        $proveedor = Proveedor::factory()->create([
            'razon_social' => 'Aceros del Sur',
            'email' => 'ventas@aceros.test',
        ]);
        $articulo = Articulo::factory()->create([
            'descripcion' => 'Tabla de pino',
            'tipo' => 'Materia Prima',
            'estado' => 'Activo',
        ]);
        $solicitud = SolicitudDeCotizacionFactory::new()->create([
            'fecha' => today(),
            'fecha_vencimiento' => today()->addWeek(),
            'estado' => $estadoSolicitud,
        ]);
        $solicitud->lineas()->create([
            'articulo_id' => $articulo->id,
            'cantidad_solicitada' => 10,
        ]);

        $cotizacion = CotizacionFactory::new()->create([
            'solicitud_de_cotizacion_id' => $solicitud->id,
            'solicitud_de_disponibilidad_id' => null,
            'proveedor_id' => $proveedor->id,
            'fecha' => today(),
        ]);

        $linea = LineaCotizacionFactory::new()->create([
            'cotizacion_id' => $cotizacion->id,
            'articulo_id' => $articulo->id,
            'precio_unitario' => 120.50,
            'cantidad_ofertada' => 8,
            'fecha_prometida' => today()->addDays(5),
            'adjudicada' => false,
        ]);

        return compact('proveedor', 'solicitud', 'articulo', 'cotizacion', 'linea');
    }

    public function test_emits_an_orden_from_a_quote_and_adjudicates_the_solicitud(): void
    {
        // Arrange
        Mail::fake();
        $actor = $this->actor();
        $contexto = $this->contexto();

        // Act
        $orden = $this->service()->emitir($actor, $contexto['cotizacion']->id, '127.0.0.1');

        // Assert
        $this->assertSame('OC-'.today()->year.'-0001', $orden->numero_oc);
        $this->assertSame('Emitida', $orden->estado);
        $this->assertSame($contexto['proveedor']->id, $orden->proveedor_id);
        $this->assertSame($contexto['cotizacion']->id, $orden->cotizacion_id);
        $this->assertSame(today()->toDateString(), $orden->fecha_emision->toDateString());
        $this->assertNotNull($orden->token);

        $this->assertDatabaseHas('lineas_de_orden_de_compra', [
            'orden_de_compra_id' => $orden->id,
            'articulo_id' => $contexto['articulo']->id,
            'cantidad' => 8,
            'precio_pactado' => 120.50,
        ]);

        $this->assertDatabaseHas('lineas_de_cotizacion', [
            'id' => $contexto['linea']->id,
            'adjudicada' => true,
        ]);
        $this->assertSame('Adjudicada', $contexto['solicitud']->refresh()->estado);

        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'emitir_orden_compra',
            'usuario_id' => $actor->id,
            'auditable_type' => OrdenDeCompra::class,
            'auditable_id' => $orden->id,
        ]);
    }

    public function test_mails_the_orden_after_the_transaction(): void
    {
        // Arrange
        Mail::fake();
        $actor = $this->actor();
        $contexto = $this->contexto();

        // Act
        $orden = $this->service()->emitir($actor, $contexto['cotizacion']->id);

        // Assert
        Mail::assertSent(OrdenDeCompraMail::class, 1);
        Mail::assertSent(
            OrdenDeCompraMail::class,
            fn (OrdenDeCompraMail $mail): bool => $mail->hasTo('ventas@aceros.test')
                && $mail->numeroOc === $orden->numero_oc
                && $mail->token === $orden->token
                && $mail->lineas[0]['articulo'] === 'Tabla de pino',
        );
    }

    public function test_numbers_are_sequential_per_year(): void
    {
        // Arrange
        Mail::fake();
        $actor = $this->actor();
        $primero = $this->contexto();
        $segundo = $this->contexto();

        // Act
        $ordenUno = $this->service()->emitir($actor, $primero['cotizacion']->id);
        $ordenDos = $this->service()->emitir($actor, $segundo['cotizacion']->id);

        // Assert
        $this->assertSame('OC-'.today()->year.'-0001', $ordenUno->numero_oc);
        $this->assertSame('OC-'.today()->year.'-0002', $ordenDos->numero_oc);
    }

    public function test_emitting_the_same_solicitud_twice_fails(): void
    {
        // Arrange
        Mail::fake();
        $actor = $this->actor();
        $contexto = $this->contexto();
        $this->service()->emitir($actor, $contexto['cotizacion']->id);

        // Act / Assert
        try {
            $this->service()->emitir($actor, $contexto['cotizacion']->id);

            $this->fail('An already adjudicated solicitud should not be emitted again.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('solicitud', $exception->errors());
        }

        $this->assertDatabaseCount('ordenes_de_compra', 1);
    }

    public function test_emitting_an_unknown_quote_fails_with_not_found(): void
    {
        // Arrange
        $actor = $this->actor();

        // Act / Assert
        $this->expectException(ModelNotFoundException::class);

        $this->service()->emitir($actor, 999999);
    }

    public function test_emitting_a_quote_from_a_non_sent_solicitud_fails(): void
    {
        // Arrange
        $actor = $this->actor();
        $contexto = $this->contexto('Borrador');

        // Act / Assert
        try {
            $this->service()->emitir($actor, $contexto['cotizacion']->id);

            $this->fail('A quote from a non-sent solicitud should not be emitted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('solicitud', $exception->errors());
        }

        $this->assertDatabaseCount('ordenes_de_compra', 0);
    }

    public function test_updates_lines_only_when_the_orden_is_emitted(): void
    {
        // Arrange
        Mail::fake();
        $actor = $this->actor();
        $contexto = $this->contexto();
        $orden = $this->service()->emitir($actor, $contexto['cotizacion']->id);
        $nuevoArticulo = Articulo::factory()->create(['descripcion' => 'Tornillo']);

        // Act
        $this->service()->actualizar($actor, $orden, [
            [
                'articulo_id' => $nuevoArticulo->id,
                'cantidad' => 3,
                'precio_pactado' => 15.75,
            ],
        ], '127.0.0.1');

        // Assert
        $this->assertDatabaseCount('lineas_de_orden_de_compra', 1);
        $this->assertDatabaseHas('lineas_de_orden_de_compra', [
            'orden_de_compra_id' => $orden->id,
            'articulo_id' => $nuevoArticulo->id,
            'cantidad' => 3,
            'precio_pactado' => 15.75,
        ]);
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'actualizar_orden_compra',
            'usuario_id' => $actor->id,
            'auditable_type' => OrdenDeCompra::class,
            'auditable_id' => $orden->id,
        ]);
    }

    public function test_updates_are_rejected_when_the_orden_is_not_emitted(): void
    {
        // Arrange
        $actor = $this->actor();
        $articulo = Articulo::factory()->create();
        $orden = OrdenDeCompraFactory::new()->create(['estado' => 'Confirmada']);
        LineaOrdenCompraFactory::new()->create([
            'orden_de_compra_id' => $orden->id,
            'articulo_id' => $articulo->id,
        ]);

        // Act / Assert
        try {
            $this->service()->actualizar($actor, $orden, [
                ['articulo_id' => $articulo->id, 'cantidad' => 2, 'precio_pactado' => 1],
            ]);

            $this->fail('A confirmed orden should not be updated.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('estado', $exception->errors());
        }

        $this->assertDatabaseCount('lineas_de_orden_de_compra', 1);
    }

    public function test_updates_reject_non_positive_quantities_and_negative_prices(): void
    {
        // Arrange
        Mail::fake();
        $actor = $this->actor();
        $contexto = $this->contexto();
        $orden = $this->service()->emitir($actor, $contexto['cotizacion']->id);

        // Act / Assert
        try {
            $this->service()->actualizar($actor, $orden, [
                ['articulo_id' => $contexto['articulo']->id, 'cantidad' => 0, 'precio_pactado' => 10],
            ]);

            $this->fail('A zero quantity should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('lineas.0.cantidad', $exception->errors());
        }

        try {
            $this->service()->actualizar($actor, $orden, [
                ['articulo_id' => $contexto['articulo']->id, 'cantidad' => 1, 'precio_pactado' => -1],
            ]);

            $this->fail('A negative price should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('lineas.0.precio_pactado', $exception->errors());
        }

        $this->assertDatabaseCount('lineas_de_orden_de_compra', 1);
    }

    public function test_annuls_an_emitted_orden_and_audits_it(): void
    {
        // Arrange
        Mail::fake();
        $actor = $this->actor();
        $contexto = $this->contexto();
        $orden = $this->service()->emitir($actor, $contexto['cotizacion']->id);

        // Act
        $this->service()->anular($actor, $orden, 'El proveedor no puede entregar', '127.0.0.1');

        // Assert
        $this->assertSame('Anulada', $orden->refresh()->estado);
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'anular_orden_compra',
            'usuario_id' => $actor->id,
            'auditable_type' => OrdenDeCompra::class,
            'auditable_id' => $orden->id,
        ]);
    }

    public function test_annulment_is_rejected_when_the_orden_is_not_emitted(): void
    {
        // Arrange
        $actor = $this->actor();
        $orden = OrdenDeCompraFactory::new()->create(['estado' => 'Confirmada']);

        // Act / Assert
        try {
            $this->service()->anular($actor, $orden, 'Sin stock');

            $this->fail('A confirmed orden should not be annulled.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('estado', $exception->errors());
        }

        $this->assertSame('Confirmada', $orden->refresh()->estado);
    }

    public function test_confirms_an_orden_through_its_token(): void
    {
        // Arrange
        Mail::fake();
        $actor = $this->actor();
        $contexto = $this->contexto();
        $orden = $this->service()->emitir($actor, $contexto['cotizacion']->id);

        // Act
        $this->service()->confirmar($orden->token, true, null, '127.0.0.1');

        // Assert
        $orden->refresh();
        $this->assertSame('Confirmada', $orden->estado);
        $this->assertNull($orden->token);
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'confirmar_orden_compra',
            'auditable_type' => OrdenDeCompra::class,
            'auditable_id' => $orden->id,
        ]);
    }

    public function test_rejects_an_orden_through_its_token_with_a_reason(): void
    {
        // Arrange
        Mail::fake();
        $actor = $this->actor();
        $contexto = $this->contexto();
        $orden = $this->service()->emitir($actor, $contexto['cotizacion']->id);

        // Act
        $this->service()->confirmar($orden->token, false, 'No hay stock suficiente');

        // Assert
        $orden->refresh();
        $this->assertSame('Rechazada', $orden->estado);
        $this->assertNull($orden->token);
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'confirmar_orden_compra',
            'auditable_type' => OrdenDeCompra::class,
            'auditable_id' => $orden->id,
        ]);
    }

    public function test_confirming_an_orden_records_the_confirmation_date(): void
    {
        // Arrange
        Mail::fake();
        $actor = $this->actor();
        $contexto = $this->contexto();
        $orden = $this->service()->emitir($actor, $contexto['cotizacion']->id);

        // Act
        $this->service()->confirmar($orden->token, true);

        // Assert
        $this->assertSame(today()->toDateString(), $orden->refresh()->fecha_confirmacion?->toDateString());
    }

    public function test_rejecting_an_orden_does_not_record_a_confirmation_date(): void
    {
        // Arrange
        Mail::fake();
        $actor = $this->actor();
        $contexto = $this->contexto();
        $orden = $this->service()->emitir($actor, $contexto['cotizacion']->id);

        // Act
        $this->service()->confirmar($orden->token, false, 'No hay stock suficiente');

        // Assert
        $this->assertNull($orden->refresh()->fecha_confirmacion);
    }

    public function test_a_token_cannot_be_used_twice(): void
    {
        // Arrange
        Mail::fake();
        $actor = $this->actor();
        $contexto = $this->contexto();
        $orden = $this->service()->emitir($actor, $contexto['cotizacion']->id);
        $token = $orden->token;
        $this->service()->confirmar($token, true);

        // Act / Assert
        try {
            $this->service()->confirmar($token, true);

            $this->fail('A used token should not be usable again.');
        } catch (ModelNotFoundException) {
            $this->assertSame('Confirmada', $orden->refresh()->estado);
        }
    }

    public function test_an_unknown_token_fails_with_not_found(): void
    {
        // Act / Assert
        $this->expectException(ModelNotFoundException::class);

        $this->service()->confirmar('00000000-0000-0000-0000-000000000000', true);
    }

    public function test_lists_ordenes_with_filters_ordering_and_fifteen_per_page(): void
    {
        // Arrange
        $aceros = Proveedor::factory()->create(['razon_social' => 'Aceros del Sur']);
        $maderas = Proveedor::factory()->create(['razon_social' => 'Maderas Norte']);

        $enero = OrdenDeCompraFactory::new()->create([
            'numero_oc' => 'OC-2026-0001',
            'proveedor_id' => $aceros->id,
            'estado' => 'Emitida',
            'fecha_emision' => '2026-01-10',
        ]);
        LineaOrdenCompraFactory::new()->count(2)->create([
            'orden_de_compra_id' => $enero->id,
            'cantidad' => 2,
            'precio_pactado' => 10,
        ]);

        $febrero = OrdenDeCompraFactory::new()->create([
            'numero_oc' => 'OC-2026-0002',
            'proveedor_id' => $maderas->id,
            'estado' => 'Anulada',
            'fecha_emision' => '2026-02-10',
        ]);
        LineaOrdenCompraFactory::new()->create([
            'orden_de_compra_id' => $febrero->id,
            'cantidad' => 5,
            'precio_pactado' => 3,
        ]);

        $marzo = OrdenDeCompraFactory::new()->create([
            'numero_oc' => 'OC-2026-0003',
            'proveedor_id' => $aceros->id,
            'estado' => 'Emitida',
            'fecha_emision' => '2026-03-10',
        ]);
        LineaOrdenCompraFactory::new()->create([
            'orden_de_compra_id' => $marzo->id,
            'cantidad' => 1,
            'precio_pactado' => 100,
        ]);

        // Act
        $todas = $this->service()->listar([]);
        $porEstado = $this->service()->listar(['estado' => 'Emitida']);
        $porProveedor = $this->service()->listar(['proveedor' => 'acer']);
        $porNumero = $this->service()->listar(['numero_oc' => '0002']);
        $porRango = $this->service()->listar([
            'fecha_desde' => '2026-02-01',
            'fecha_hasta' => '2026-02-28',
        ]);

        // Assert
        $this->assertSame(15, $todas->perPage());
        $this->assertSame(3, $todas->total());
        $this->assertSame(
            ['id', 'numero_oc', 'proveedor', 'estado', 'fecha_emision', 'lineas', 'total'],
            array_keys($todas->items()[0]),
        );
        $this->assertSame([$marzo->id, $febrero->id, $enero->id], array_column($todas->items(), 'id'));
        $this->assertSame(1, $todas->items()[0]['lineas']);
        $this->assertSame(100.0, $todas->items()[0]['total']);

        $this->assertSame(2, $porEstado->total());
        $this->assertSame([$marzo->id, $enero->id], array_column($porEstado->items(), 'id'));

        $this->assertSame(2, $porProveedor->total());
        $this->assertSame([$marzo->id, $enero->id], array_column($porProveedor->items(), 'id'));

        $this->assertSame(1, $porNumero->total());
        $this->assertSame($febrero->id, $porNumero->items()[0]['id']);

        $this->assertSame(1, $porRango->total());
        $this->assertSame($febrero->id, $porRango->items()[0]['id']);
    }

    public function test_lists_sent_solicitudes_with_their_quotes_and_totals(): void
    {
        // Arrange
        $proveedor = Proveedor::factory()->create(['razon_social' => 'Aceros del Sur']);
        $solicitud = SolicitudDeCotizacionFactory::new()->create([
            'fecha' => '2026-02-02',
            'fecha_vencimiento' => '2026-02-20',
            'estado' => 'Enviada',
        ]);
        $cotizacion = CotizacionFactory::new()->create([
            'solicitud_de_cotizacion_id' => $solicitud->id,
            'proveedor_id' => $proveedor->id,
        ]);
        LineaCotizacionFactory::new()->create([
            'cotizacion_id' => $cotizacion->id,
            'precio_unitario' => 100,
            'cantidad_ofertada' => 2,
            'fecha_prometida' => today()->addDays(3),
        ]);
        LineaCotizacionFactory::new()->create([
            'cotizacion_id' => $cotizacion->id,
            'precio_unitario' => 50,
            'cantidad_ofertada' => 1,
            'fecha_prometida' => today()->addDays(9),
        ]);

        SolicitudDeCotizacionFactory::new()->create(['estado' => 'Enviada']);
        $borrador = SolicitudDeCotizacionFactory::new()->create(['estado' => 'Borrador']);
        CotizacionFactory::new()->create([
            'solicitud_de_cotizacion_id' => $borrador->id,
            'proveedor_id' => $proveedor->id,
        ]);

        // Act
        $resultado = $this->service()->solicitudesConCotizaciones();

        // Assert
        $this->assertCount(1, $resultado);
        $this->assertSame(['id', 'fecha', 'fecha_vencimiento', 'cotizaciones'], array_keys($resultado[0]));
        $this->assertSame($solicitud->id, $resultado[0]['id']);
        $this->assertSame('2026-02-02', $resultado[0]['fecha']);
        $this->assertSame('2026-02-20', $resultado[0]['fecha_vencimiento']);
        $this->assertCount(1, $resultado[0]['cotizaciones']);
        $this->assertSame(
            ['id', 'proveedor', 'total', 'ultima_fecha_prometida'],
            array_keys($resultado[0]['cotizaciones'][0]),
        );
        $this->assertSame('Aceros del Sur', $resultado[0]['cotizaciones'][0]['proveedor']);
        $this->assertSame(250.0, $resultado[0]['cotizaciones'][0]['total']);
        $this->assertSame(today()->addDays(9)->toDateString(), $resultado[0]['cotizaciones'][0]['ultima_fecha_prometida']);
    }

    public function test_only_purchasing_manager_permission_can_manage_ordenes(): void
    {
        // Arrange
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $encargado = Usuario::factory()->create();
        $encargado->assignRole(Rol::EncargadoCompras->value);

        $gerente = Usuario::factory()->create();
        $gerente->assignRole(Rol::Gerente->value);

        // Act / Assert
        $this->assertTrue($this->service()->puedeGestionar($encargado));
        $this->assertFalse($this->service()->puedeGestionar($gerente));
    }
}
