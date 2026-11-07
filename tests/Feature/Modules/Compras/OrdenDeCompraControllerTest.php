<?php

namespace Tests\Feature\Modules\Compras;

use App\Modules\Compras\Models\Cotizacion;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Models\SolicitudDeCotizacion;
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
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class OrdenDeCompraControllerTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioConRol(Rol $rol): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole($rol->value);

        return $usuario;
    }

    /**
     * @return array{proveedor: Proveedor, solicitud: SolicitudDeCotizacion, articulo: Articulo, cotizacion: Cotizacion}
     */
    private function contexto(): array
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
            'fecha' => today(),
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

        return compact('proveedor', 'solicitud', 'articulo', 'cotizacion');
    }

    public function test_guests_are_redirected_to_the_login_screen(): void
    {
        // Act
        $response = $this->get('/compras/ordenes-de-compra');

        // Assert
        $response->assertRedirect('/login');
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->get('/compras/ordenes-de-compra');

        // Assert
        $response->assertForbidden();
    }

    public function test_a_purchasing_manager_can_list_ordenes(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoCompras);
        $contexto = $this->contexto();
        OrdenDeCompraFactory::new()->create([
            'proveedor_id' => $contexto['proveedor']->id,
            'cotizacion_id' => $contexto['cotizacion']->id,
            'solicitud_de_disponibilidad_id' => null,
            'estado' => 'Emitida',
        ]);

        // Act
        $response = $this->actingAs($usuario)->get('/compras/ordenes-de-compra');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Compras/OrdenesDeCompra/Index')
            ->has('ordenes.data', 1)
            ->has('solicitudesConCotizaciones', 1)
            ->has('solicitudesConCotizaciones.0.cotizaciones', 1)
            ->has('articulos'));
    }

    public function test_a_purchasing_manager_can_emit_an_orden(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoCompras);
        $contexto = $this->contexto();

        // Act
        $response = $this->actingAs($usuario)->post('/compras/ordenes-de-compra', [
            'cotizacion_id' => $contexto['cotizacion']->id,
        ]);

        // Assert
        $response->assertRedirect('/compras/ordenes-de-compra');
        $response->assertSessionHas('success', 'Orden de compra emitida.');
        $this->assertDatabaseHas('ordenes_de_compra', [
            'cotizacion_id' => $contexto['cotizacion']->id,
            'proveedor_id' => $contexto['proveedor']->id,
            'estado' => 'Emitida',
        ]);
        $this->assertSame('Adjudicada', $contexto['solicitud']->refresh()->estado);
    }

    public function test_emitting_the_same_solicitud_twice_fails_validation(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoCompras);
        $contexto = $this->contexto();
        $this->actingAs($usuario)->post('/compras/ordenes-de-compra', [
            'cotizacion_id' => $contexto['cotizacion']->id,
        ]);

        // Act
        $response = $this->actingAs($usuario)->post('/compras/ordenes-de-compra', [
            'cotizacion_id' => $contexto['cotizacion']->id,
        ]);

        // Assert
        $response->assertSessionHasErrors('solicitud');
        $this->assertDatabaseCount('ordenes_de_compra', 1);
    }

    public function test_a_purchasing_manager_can_update_an_emitted_orden(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoCompras);
        $articulo = Articulo::factory()->create(['descripcion' => 'Tornillo']);
        $orden = OrdenDeCompraFactory::new()->create(['estado' => 'Emitida']);

        // Act
        $response = $this->actingAs($usuario)->put("/compras/ordenes-de-compra/{$orden->id}", [
            'lineas' => [
                ['articulo_id' => $articulo->id, 'cantidad' => 4, 'precio_pactado' => 9.5],
            ],
        ]);

        // Assert
        $response->assertRedirect('/compras/ordenes-de-compra');
        $response->assertSessionHas('success', 'Orden de compra actualizada.');
        $this->assertDatabaseHas('lineas_de_orden_de_compra', [
            'orden_de_compra_id' => $orden->id,
            'articulo_id' => $articulo->id,
            'cantidad' => 4,
            'precio_pactado' => 9.5,
        ]);
    }

    public function test_updating_requires_at_least_one_line(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoCompras);
        $orden = OrdenDeCompraFactory::new()->create(['estado' => 'Emitida']);

        // Act
        $response = $this->actingAs($usuario)->put("/compras/ordenes-de-compra/{$orden->id}", [
            'lineas' => [],
        ]);

        // Assert
        $response->assertSessionHasErrors('lineas');
    }

    public function test_a_purchasing_manager_can_annul_an_emitted_orden(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoCompras);
        $orden = OrdenDeCompraFactory::new()->create(['estado' => 'Emitida']);

        // Act
        $response = $this->actingAs($usuario)->post(
            "/compras/ordenes-de-compra/{$orden->id}/anular",
            ['motivo' => 'El proveedor no puede entregar'],
        );

        // Assert
        $response->assertRedirect('/compras/ordenes-de-compra');
        $response->assertSessionHas('success', 'Orden de compra anulada.');
        $this->assertDatabaseHas('ordenes_de_compra', [
            'id' => $orden->id,
            'estado' => 'Anulada',
        ]);
    }

    public function test_emitting_requires_a_quote_id(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoCompras);

        // Act
        $response = $this->actingAs($usuario)->post('/compras/ordenes-de-compra', []);

        // Assert
        $response->assertSessionHasErrors('cotizacion_id');
        $this->assertDatabaseCount('ordenes_de_compra', 0);
    }

    public function test_an_unknown_orden_returns_not_found(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoCompras);
        $articulo = Articulo::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->put('/compras/ordenes-de-compra/999999', [
            'lineas' => [
                ['articulo_id' => $articulo->id, 'cantidad' => 1, 'precio_pactado' => 1],
            ],
        ]);

        // Assert
        $response->assertNotFound();
    }
}
