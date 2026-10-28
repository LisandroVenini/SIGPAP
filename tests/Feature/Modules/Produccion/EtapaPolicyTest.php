<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Produccion\Models\Etapa;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class EtapaPolicyTest extends TestCase
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

    public function test_administrador_and_supervisor_can_manage_etapas(): void
    {
        // Arrange
        $admin = $this->usuarioConRol(Rol::Administrador);
        $supervisor = $this->usuarioConRol(Rol::SupervisorProduccion);
        $etapa = Etapa::factory()->create();

        // Act / Assert
        foreach ([$admin, $supervisor] as $actor) {
            $this->assertTrue(Gate::forUser($actor)->allows('viewAny', Etapa::class));
            $this->assertTrue(Gate::forUser($actor)->allows('create', Etapa::class));
            $this->assertTrue(Gate::forUser($actor)->allows('update', $etapa));
            $this->assertTrue(Gate::forUser($actor)->allows('delete', $etapa));
        }
    }

    public function test_other_roles_cannot_manage_etapas(): void
    {
        // Arrange
        $gerente = $this->usuarioConRol(Rol::Gerente);
        $sinRol = $this->usuarioConRol();
        $etapa = Etapa::factory()->create();

        // Act / Assert
        foreach ([$gerente, $sinRol] as $actor) {
            $this->assertFalse(Gate::forUser($actor)->allows('viewAny', Etapa::class));
            $this->assertFalse(Gate::forUser($actor)->allows('create', Etapa::class));
            $this->assertFalse(Gate::forUser($actor)->allows('update', $etapa));
            $this->assertFalse(Gate::forUser($actor)->allows('delete', $etapa));
        }
    }
}
