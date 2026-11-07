<?php

namespace Tests\Feature\Modules\Compras;

use App\Modules\Compras\Models\Proveedor;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ProveedorControllerTest extends TestCase
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
        $response = $this->get('/compras/proveedores');

        // Assert
        $response->assertRedirect('/login');
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->get('/compras/proveedores');

        // Assert
        $response->assertForbidden();
    }

    public function test_a_purchasing_manager_can_list_suppliers(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoCompras);
        Proveedor::factory()->create(['razon_social' => 'Aceros del Sur']);

        // Act
        $response = $this->actingAs($usuario)->get('/compras/proveedores');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Compras/Proveedores/Index')
            ->has('proveedores.data')
            ->has('filtros'));
    }

    public function test_a_purchasing_manager_can_create_a_supplier(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoCompras);

        // Act
        $response = $this->actingAs($usuario)->post('/compras/proveedores', [
            'razon_social' => 'Aceros del Sur S.A.',
            'cuit' => '30-71234567-8',
            'email' => 'compras@aceros.test',
            'telefono' => '11-4444-5555',
        ]);

        // Assert
        $response->assertRedirect('/compras/proveedores');
        $response->assertSessionHas('success', 'Proveedor creado.');
        $this->assertDatabaseHas('proveedores', [
            'razon_social' => 'Aceros del Sur S.A.',
            'cuit' => '30-71234567-8',
            'estado' => 'Activo',
        ]);
    }

    public function test_a_purchasing_manager_can_update_a_supplier_without_changing_the_cuit(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoCompras);
        $proveedor = Proveedor::factory()->create([
            'razon_social' => 'Aceros del Sur',
            'cuit' => '30-71234567-8',
        ]);

        // Act
        $response = $this->actingAs($usuario)->put("/compras/proveedores/{$proveedor->id}", [
            'razon_social' => 'Aceros del Sur S.A.',
            'cuit' => '20-99999999-9',
            'email' => 'nuevo@aceros.test',
            'telefono' => '11-2222-2222',
        ]);

        // Assert
        $response->assertRedirect('/compras/proveedores');
        $response->assertSessionHas('success', 'Proveedor actualizado.');
        $this->assertDatabaseHas('proveedores', [
            'id' => $proveedor->id,
            'razon_social' => 'Aceros del Sur S.A.',
            'cuit' => '30-71234567-8',
            'email' => 'nuevo@aceros.test',
            'telefono' => '11-2222-2222',
        ]);
    }

    public function test_a_purchasing_manager_can_deactivate_a_supplier(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoCompras);
        $proveedor = Proveedor::factory()->create(['estado' => 'Activo']);

        // Act
        $response = $this->actingAs($usuario)->delete("/compras/proveedores/{$proveedor->id}");

        // Assert
        $response->assertRedirect('/compras/proveedores');
        $response->assertSessionHas('success', 'Proveedor dado de baja.');
        $this->assertDatabaseHas('proveedores', [
            'id' => $proveedor->id,
            'estado' => 'Inactivo',
        ]);
    }
}
