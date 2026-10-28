<?php

namespace Tests\Feature\Modules\Inventario;

use App\Modules\Inventario\Models\Articulo;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ArticuloPolicyTest extends TestCase
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

    public function test_administrador_and_encargado_de_stock_can_manage_articulos(): void
    {
        // Arrange
        $admin = $this->usuarioConRol(Rol::Administrador);
        $stock = $this->usuarioConRol(Rol::EncargadoStock);
        $articulo = Articulo::factory()->create();

        // Act / Assert
        foreach ([$admin, $stock] as $actor) {
            $this->assertTrue(Gate::forUser($actor)->allows('viewAny', Articulo::class));
            $this->assertTrue(Gate::forUser($actor)->allows('create', Articulo::class));
            $this->assertTrue(Gate::forUser($actor)->allows('update', $articulo));
            $this->assertTrue(Gate::forUser($actor)->allows('cambiarEstado', $articulo));
            $this->assertTrue(Gate::forUser($actor)->allows('ajustar', $articulo));
        }
    }

    public function test_other_roles_cannot_manage_articulos(): void
    {
        // Arrange
        $gerente = $this->usuarioConRol(Rol::Gerente);
        $sinRol = $this->usuarioConRol();
        $articulo = Articulo::factory()->create();

        // Act / Assert
        foreach ([$gerente, $sinRol] as $actor) {
            $this->assertFalse(Gate::forUser($actor)->allows('viewAny', Articulo::class));
            $this->assertFalse(Gate::forUser($actor)->allows('create', Articulo::class));
            $this->assertFalse(Gate::forUser($actor)->allows('update', $articulo));
            $this->assertFalse(Gate::forUser($actor)->allows('cambiarEstado', $articulo));
            $this->assertFalse(Gate::forUser($actor)->allows('ajustar', $articulo));
        }
    }
}
