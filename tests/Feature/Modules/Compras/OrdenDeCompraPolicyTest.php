<?php

namespace Tests\Feature\Modules\Compras;

use App\Modules\Compras\Models\OrdenDeCompra;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Factories\OrdenDeCompraFactory;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class OrdenDeCompraPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioConRol(?Rol $rol = null): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();

        if ($rol !== null) {
            $usuario->assignRole($rol->value);
        }

        return $usuario;
    }

    public function test_administrator_and_purchasing_manager_can_manage_ordenes(): void
    {
        // Arrange
        $admin = $this->usuarioConRol(Rol::Administrador);
        $encargado = $this->usuarioConRol(Rol::EncargadoCompras);
        $orden = OrdenDeCompraFactory::new()->create();

        // Act / Assert
        foreach ([$admin, $encargado] as $actor) {
            $this->assertTrue(Gate::forUser($actor)->allows('viewAny', OrdenDeCompra::class));
            $this->assertTrue(Gate::forUser($actor)->allows('create', OrdenDeCompra::class));
            $this->assertTrue(Gate::forUser($actor)->allows('update', $orden));
            $this->assertTrue(Gate::forUser($actor)->allows('delete', $orden));
        }
    }

    public function test_other_roles_cannot_manage_ordenes(): void
    {
        // Arrange
        $gerente = $this->usuarioConRol(Rol::Gerente);
        $sinRol = $this->usuarioConRol();
        $orden = OrdenDeCompraFactory::new()->create();

        // Act / Assert
        foreach ([$gerente, $sinRol] as $actor) {
            $this->assertFalse(Gate::forUser($actor)->allows('viewAny', OrdenDeCompra::class));
            $this->assertFalse(Gate::forUser($actor)->allows('create', OrdenDeCompra::class));
            $this->assertFalse(Gate::forUser($actor)->allows('update', $orden));
            $this->assertFalse(Gate::forUser($actor)->allows('delete', $orden));
        }
    }
}
