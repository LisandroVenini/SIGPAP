<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Produccion\Models\Maquinaria;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class MaquinariaPolicyTest extends TestCase
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

    public function test_administrator_and_supervisor_can_manage_machines(): void
    {
        // Arrange
        $admin = $this->usuarioConRol(Rol::Administrador);
        $supervisor = $this->usuarioConRol(Rol::SupervisorProduccion);
        $maquinaria = Maquinaria::factory()->create();

        // Act / Assert
        foreach ([$admin, $supervisor] as $actor) {
            $this->assertTrue(Gate::forUser($actor)->allows('viewAny', Maquinaria::class));
            $this->assertTrue(Gate::forUser($actor)->allows('create', Maquinaria::class));
            $this->assertTrue(Gate::forUser($actor)->allows('update', $maquinaria));
            $this->assertTrue(Gate::forUser($actor)->allows('delete', $maquinaria));
        }
    }

    public function test_other_roles_cannot_manage_machines(): void
    {
        // Arrange
        $gerente = $this->usuarioConRol(Rol::Gerente);
        $sinRol = $this->usuarioConRol();
        $maquinaria = Maquinaria::factory()->create();

        // Act / Assert
        foreach ([$gerente, $sinRol] as $actor) {
            $this->assertFalse(Gate::forUser($actor)->allows('viewAny', Maquinaria::class));
            $this->assertFalse(Gate::forUser($actor)->allows('create', Maquinaria::class));
            $this->assertFalse(Gate::forUser($actor)->allows('update', $maquinaria));
            $this->assertFalse(Gate::forUser($actor)->allows('delete', $maquinaria));
        }
    }
}
