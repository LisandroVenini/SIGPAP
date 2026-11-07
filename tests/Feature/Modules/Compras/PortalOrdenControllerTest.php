<?php

namespace Tests\Feature\Modules\Compras;

use App\Modules\Compras\Models\OrdenDeCompra;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Services\GestionOrdenesDeCompraService;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Factories\CotizacionFactory;
use Database\Factories\LineaCotizacionFactory;
use Database\Factories\OrdenDeCompraFactory;
use Database\Factories\SolicitudDeCotizacionFactory;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PortalOrdenControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole(Rol::EncargadoCompras->value);

        return $usuario;
    }

    /**
     * @return array{orden: OrdenDeCompra, articulo: Articulo, proveedor: Proveedor}
     */
    private function ordenEmitida(): array
    {
        Mail::fake();

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
            'fecha_vencimiento' => today()->addWeek(),
            'estado' => 'Enviada',
        ]);
        $solicitud->lineas()->create([
            'articulo_id' => $articulo->id,
            'cantidad_solicitada' => 10,
        ]);
        $cotizacion = CotizacionFactory::new()->create([
            'solicitud_de_cotizacion_id' => $solicitud->id,
            'solicitud_de_disponibilidad_id' => null,
            'proveedor_id' => $proveedor->id,
        ]);
        LineaCotizacionFactory::new()->create([
            'cotizacion_id' => $cotizacion->id,
            'articulo_id' => $articulo->id,
            'precio_unitario' => 120.50,
            'cantidad_ofertada' => 8,
            'fecha_prometida' => today()->addDays(5),
        ]);

        $orden = app(GestionOrdenesDeCompraService::class)->emitir(
            $this->actor(),
            $cotizacion->id,
        );

        return compact('orden', 'articulo', 'proveedor');
    }

    public function test_a_valid_token_opens_the_orden(): void
    {
        // Arrange
        $contexto = $this->ordenEmitida();

        // Act
        $response = $this->get("/portal/ordenes/{$contexto['orden']->token}");

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Portal/Orden')
            ->where('orden.numero_oc', $contexto['orden']->numero_oc)
            ->where('orden.proveedor', 'Aceros del Sur')
            ->where('orden.fecha_emision', today()->toDateString())
            ->has('orden.lineas', 1)
            ->where('orden.lineas.0.articulo', 'Tabla de pino')
            ->where('orden.lineas.0.cantidad', 8)
            ->where('orden.lineas.0.precio_pactado', 120.5)
            ->where('orden.total', 964)
            ->where('token', $contexto['orden']->token));
    }

    public function test_the_token_can_confirm_the_orden(): void
    {
        // Arrange
        $contexto = $this->ordenEmitida();
        $token = $contexto['orden']->token;

        // Act
        $response = $this->post("/portal/ordenes/{$token}", ['aceptada' => true]);

        // Assert
        $response->assertRedirect("/portal/ordenes/{$token}");
        $response->assertSessionHas('orden_resuelta', 'Confirmada');

        $contexto['orden']->refresh();
        $this->assertSame('Confirmada', $contexto['orden']->estado);
        $this->assertNull($contexto['orden']->token);
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'confirmar_orden_compra',
            'auditable_type' => OrdenDeCompra::class,
            'auditable_id' => $contexto['orden']->id,
        ]);
    }

    public function test_the_confirmation_is_shown_on_the_result_screen(): void
    {
        // Arrange
        $contexto = $this->ordenEmitida();
        $token = $contexto['orden']->token;
        $this->post("/portal/ordenes/{$token}", ['aceptada' => true]);

        // Act
        $response = $this->get("/portal/ordenes/{$token}");

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Portal/Orden')
            ->where('resultado', 'Confirmada'));
    }

    public function test_the_token_can_reject_the_orden_with_a_reason(): void
    {
        // Arrange
        $contexto = $this->ordenEmitida();
        $token = $contexto['orden']->token;

        // Act
        $response = $this->post("/portal/ordenes/{$token}", [
            'aceptada' => false,
            'motivo' => 'No hay stock suficiente',
        ]);

        // Assert
        $response->assertRedirect("/portal/ordenes/{$token}");
        $response->assertSessionHas('orden_resuelta', 'Rechazada');
        $this->assertSame('Rechazada', $contexto['orden']->refresh()->estado);
    }

    public function test_rejecting_requires_a_reason(): void
    {
        // Arrange
        $contexto = $this->ordenEmitida();
        $token = $contexto['orden']->token;

        // Act
        $response = $this->post("/portal/ordenes/{$token}", ['aceptada' => false]);

        // Assert
        $response->assertSessionHasErrors('motivo');
        $this->assertSame('Emitida', $contexto['orden']->refresh()->estado);
    }

    public function test_an_unknown_token_returns_not_found(): void
    {
        // Act
        $response = $this->get('/portal/ordenes/'.Str::uuid());

        // Assert
        $response->assertNotFound();
    }

    public function test_a_non_emitted_orden_token_returns_not_found(): void
    {
        // Arrange
        $token = (string) Str::uuid();
        OrdenDeCompraFactory::new()->create([
            'estado' => 'Confirmada',
            'token' => $token,
        ]);

        // Act
        $response = $this->get("/portal/ordenes/{$token}");

        // Assert
        $response->assertNotFound();
    }

    public function test_a_token_cannot_be_used_twice(): void
    {
        // Arrange
        $contexto = $this->ordenEmitida();
        $token = $contexto['orden']->token;
        $this->post("/portal/ordenes/{$token}", ['aceptada' => true]);

        // Act
        $response = $this->post("/portal/ordenes/{$token}", ['aceptada' => true]);

        // Assert
        $response->assertNotFound();
    }

    public function test_the_public_orden_page_is_rate_limited(): void
    {
        // Act / Assert
        for ($intento = 0; $intento < 20; $intento++) {
            $this->get('/portal/ordenes/'.Str::uuid())->assertNotFound();
        }

        $this->get('/portal/ordenes/'.Str::uuid())->assertStatus(429);
    }

    public function test_the_public_confirmation_is_rate_limited(): void
    {
        // Act / Assert
        for ($intento = 0; $intento < 20; $intento++) {
            $this->post('/portal/ordenes/'.Str::uuid(), ['aceptada' => true])->assertNotFound();
        }

        $this->post('/portal/ordenes/'.Str::uuid(), ['aceptada' => true])->assertStatus(429);
    }
}
