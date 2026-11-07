<?php

namespace Tests\Feature\Modules\Compras;

use App\Modules\Compras\Models\Proveedor;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Factories\SolicitudDeCotizacionFactory;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SolicitudDeCotizacionControllerTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioConRol(Rol $rol): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole($rol->value);

        return $usuario;
    }

    public function test_guests_are_redirected_to_the_login_screen(): void
    {
        // Act
        $response = $this->get('/compras/solicitudes-de-cotizacion');

        // Assert
        $response->assertRedirect('/login');
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->get('/compras/solicitudes-de-cotizacion');

        // Assert
        $response->assertForbidden();
    }

    public function test_a_purchasing_manager_can_list_solicitudes(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoCompras);
        SolicitudDeCotizacionFactory::new()->create(['estado' => 'Enviada']);

        // Act
        $response = $this->actingAs($usuario)->get('/compras/solicitudes-de-cotizacion');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Compras/SolicitudesDeCotizacion/Index')
            ->has('solicitudes.data')
            ->has('articulos')
            ->has('proveedores'));
    }

    public function test_a_purchasing_manager_can_list_solicitudes_filtered_by_criteria(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoCompras);
        $aceros = Proveedor::factory()->create(['razon_social' => 'Aceros del Sur']);
        $maderas = Proveedor::factory()->create(['razon_social' => 'Maderas Norte']);

        $coincidente = SolicitudDeCotizacionFactory::new()->create([
            'fecha' => '2026-05-10',
            'estado' => 'Enviada',
        ]);
        $coincidente->proveedores()->attach($aceros->id, ['token' => null]);

        $descartada = SolicitudDeCotizacionFactory::new()->create([
            'fecha' => '2026-05-11',
            'estado' => 'Anulada',
        ]);
        $descartada->proveedores()->attach($maderas->id, ['token' => null]);

        // Act
        $response = $this->actingAs($usuario)->get('/compras/solicitudes-de-cotizacion?'.http_build_query([
            'estado' => 'Enviada',
            'proveedor' => 'acer',
            'fecha_desde' => '2026-05-01',
            'fecha_hasta' => '2026-05-31',
        ]));

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Compras/SolicitudesDeCotizacion/Index')
            ->has('solicitudes.data', 1)
            ->where('solicitudes.data.0.id', $coincidente->id));
    }

    public function test_a_purchasing_manager_can_create_a_solicitud(): void
    {
        // Arrange
        Mail::fake();
        $usuario = $this->usuarioConRol(Rol::EncargadoCompras);
        $articulo = Articulo::factory()->create([
            'tipo' => 'Materia Prima',
            'estado' => 'Activo',
        ]);
        $proveedor = Proveedor::factory()->create([
            'email' => 'ventas@aceros.test',
            'estado' => 'Activo',
        ]);

        // Act
        $response = $this->actingAs($usuario)->post('/compras/solicitudes-de-cotizacion', [
            'fecha_vencimiento' => today()->addWeek()->toDateString(),
            'lineas' => [
                ['articulo_id' => $articulo->id, 'cantidad_solicitada' => 4],
            ],
            'proveedores' => [$proveedor->id],
        ]);

        // Assert
        $response->assertRedirect('/compras/solicitudes-de-cotizacion');
        $response->assertSessionHas('success', 'Solicitud de cotización creada.');
        $this->assertDatabaseHas('solicitudes_de_cotizacion', ['estado' => 'Enviada']);
        $this->assertDatabaseHas('lineas_de_solicitud_de_cotizacion', [
            'articulo_id' => $articulo->id,
            'cantidad_solicitada' => 4,
        ]);
        $this->assertDatabaseHas('solicitud_de_cotizacion_proveedor', [
            'proveedor_id' => $proveedor->id,
        ]);
    }

    public function test_a_purchasing_manager_can_annul_a_solicitud(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoCompras);
        $solicitud = SolicitudDeCotizacionFactory::new()->create(['estado' => 'Enviada']);

        // Act
        $response = $this->actingAs($usuario)->post(
            "/compras/solicitudes-de-cotizacion/{$solicitud->id}/anular",
            ['motivo' => 'El proveedor cerró el catálogo'],
        );

        // Assert
        $response->assertRedirect('/compras/solicitudes-de-cotizacion');
        $response->assertSessionHas('success', 'Solicitud de cotización anulada.');
        $this->assertDatabaseHas('solicitudes_de_cotizacion', [
            'id' => $solicitud->id,
            'estado' => 'Anulada',
        ]);
    }

    public function test_a_purchasing_manager_can_update_a_solicitud(): void
    {
        // Arrange
        Mail::fake();
        $usuario = $this->usuarioConRol(Rol::EncargadoCompras);
        $solicitud = SolicitudDeCotizacionFactory::new()->create(['estado' => 'Enviada']);
        $articulo = Articulo::factory()->create([
            'tipo' => 'Materia Prima',
            'estado' => 'Activo',
        ]);
        $proveedor = Proveedor::factory()->create([
            'email' => 'ventas@aceros.test',
            'estado' => 'Activo',
        ]);

        // Act
        $response = $this->actingAs($usuario)->put(
            "/compras/solicitudes-de-cotizacion/{$solicitud->id}",
            [
                'fecha_vencimiento' => today()->addDays(10)->toDateString(),
                'lineas' => [
                    ['articulo_id' => $articulo->id, 'cantidad_solicitada' => 6],
                ],
                'proveedores' => [$proveedor->id],
            ],
        );

        // Assert
        $response->assertRedirect('/compras/solicitudes-de-cotizacion');
        $response->assertSessionHas('success', 'Solicitud de cotización actualizada.');
        $this->assertDatabaseHas('solicitudes_de_cotizacion', [
            'id' => $solicitud->id,
            'fecha_vencimiento' => today()->addDays(10)->toDateString(),
        ]);
        $this->assertDatabaseHas('lineas_de_solicitud_de_cotizacion', [
            'solicitud_de_cotizacion_id' => $solicitud->id,
            'articulo_id' => $articulo->id,
        ]);
    }

    public function test_a_solicitud_cannot_be_created_without_lines_or_providers(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoCompras);

        // Act
        $response = $this->actingAs($usuario)->post('/compras/solicitudes-de-cotizacion', [
            'fecha_vencimiento' => today()->addWeek()->toDateString(),
            'lineas' => [],
            'proveedores' => [],
        ]);

        // Assert
        $response->assertSessionHasErrors(['lineas', 'proveedores']);
        $this->assertDatabaseCount('solicitudes_de_cotizacion', 0);
    }

    public function test_a_solicitud_cannot_be_created_with_a_past_expiration_date(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoCompras);
        $articulo = Articulo::factory()->create(['tipo' => 'Materia Prima', 'estado' => 'Activo']);
        $proveedor = Proveedor::factory()->create(['email' => 'ventas@aceros.test']);

        // Act
        $response = $this->actingAs($usuario)->post('/compras/solicitudes-de-cotizacion', [
            'fecha_vencimiento' => today()->subDay()->toDateString(),
            'lineas' => [
                ['articulo_id' => $articulo->id, 'cantidad_solicitada' => 1],
            ],
            'proveedores' => [$proveedor->id],
        ]);

        // Assert
        $response->assertSessionHasErrors('fecha_vencimiento');
        $this->assertDatabaseCount('solicitudes_de_cotizacion', 0);
    }

    public function test_an_unknown_solicitud_returns_not_found_on_update(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoCompras);

        // Act
        $response = $this->actingAs($usuario)->put('/compras/solicitudes-de-cotizacion/999999', [
            'fecha_vencimiento' => today()->addWeek()->toDateString(),
            'lineas' => [
                ['articulo_id' => 1, 'cantidad_solicitada' => 1],
            ],
            'proveedores' => [1],
        ]);

        // Assert
        $response->assertNotFound();
    }
}
