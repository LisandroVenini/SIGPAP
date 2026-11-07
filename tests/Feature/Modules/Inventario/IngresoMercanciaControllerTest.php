<?php

namespace Tests\Feature\Modules\Inventario;

use App\Modules\Compras\Models\OrdenDeCompra;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Factories\LineaOrdenCompraFactory;
use Database\Factories\OrdenDeCompraFactory;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class IngresoMercanciaControllerTest extends TestCase
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
     * @return array{orden: OrdenDeCompra, articulo: Articulo}
     */
    private function contexto(float $cantidadPedida = 5): array
    {
        $articulo = Articulo::factory()->create(['stock_fisico' => 0]);
        $orden = OrdenDeCompraFactory::new()->create(['estado' => 'Confirmada']);
        LineaOrdenCompraFactory::new()->create([
            'orden_de_compra_id' => $orden->id,
            'articulo_id' => $articulo->id,
            'cantidad' => $cantidadPedida,
        ]);

        return compact('orden', 'articulo');
    }

    public function test_guests_are_redirected_to_the_login_screen(): void
    {
        // Act
        $response = $this->get('/inventario/ingresos');

        // Assert
        $response->assertRedirect('/login');
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->get('/inventario/ingresos');

        // Assert
        $response->assertForbidden();
    }

    public function test_an_stock_manager_can_view_the_reception_page(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoStock);
        $contexto = $this->contexto();

        // Act
        $response = $this->actingAs($usuario)->get('/inventario/ingresos');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Inventario/Ingresos/Index')
            ->has('ordenesRecepcionables', 1)
            ->where('ordenesRecepcionables.0.numero_oc', $contexto['orden']->numero_oc)
            ->has('filtros'));
    }

    public function test_an_stock_manager_can_register_a_reception(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoStock);
        $contexto = $this->contexto();

        // Act
        $response = $this->actingAs($usuario)->post('/inventario/ingresos', [
            'numero_oc' => $contexto['orden']->numero_oc,
            'numero_remito' => 'REM-001',
            'entrega_parcial' => false,
            'confirmar_excedente' => false,
            'lineas' => [
                ['articulo_id' => $contexto['articulo']->id, 'cantidad' => 5],
            ],
        ]);

        // Assert
        $response->assertRedirect('/inventario/ingresos');
        $response->assertSessionHas('success', 'Ingreso registrado.');
        $this->assertSame(5.0, (float) $contexto['articulo']->refresh()->stock_fisico);
        $this->assertSame('Recibida', $contexto['orden']->refresh()->estado);
    }

    public function test_an_excedente_without_confirmation_fails_validation(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoStock);
        $contexto = $this->contexto(cantidadPedida: 5);

        // Act
        $response = $this->actingAs($usuario)->post('/inventario/ingresos', [
            'numero_oc' => $contexto['orden']->numero_oc,
            'numero_remito' => 'REM-001',
            'lineas' => [
                ['articulo_id' => $contexto['articulo']->id, 'cantidad' => 8],
            ],
        ]);

        // Assert
        $response->assertSessionHasErrors('lineas');
        $this->assertSame(0.0, (float) $contexto['articulo']->refresh()->stock_fisico);
        $this->assertSame('Confirmada', $contexto['orden']->refresh()->estado);
    }

    public function test_a_remito_is_required(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoStock);
        $contexto = $this->contexto();

        // Act
        $response = $this->actingAs($usuario)->post('/inventario/ingresos', [
            'numero_oc' => $contexto['orden']->numero_oc,
            'lineas' => [
                ['articulo_id' => $contexto['articulo']->id, 'cantidad' => 5],
            ],
        ]);

        // Assert
        $response->assertSessionHasErrors('numero_remito');
        $this->assertSame(0.0, (float) $contexto['articulo']->refresh()->stock_fisico);
    }
}
