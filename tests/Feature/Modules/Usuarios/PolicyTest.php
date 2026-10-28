<?php

namespace Tests\Feature\Modules\Usuarios;

use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\ParametroGlobal;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioConRol(?Rol $rol = null): Usuario
    {
        $this->seed(RoleSeeder::class);

        $usuario = Usuario::factory()->create();

        if ($rol !== null) {
            $usuario->assignRole($rol->value);
        }

        return $usuario;
    }

    public function test_usuario_policy_allows_only_administradores(): void
    {
        // Arrange
        $admin = $this->usuarioConRol(Rol::Administrador);
        $gerente = $this->usuarioConRol(Rol::Gerente);

        // Act / Assert
        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', Usuario::class));
        $this->assertTrue(Gate::forUser($admin)->allows('create', Usuario::class));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $gerente));
        $this->assertTrue(Gate::forUser($admin)->allows('cambiarEstado', $gerente));

        $this->assertFalse(Gate::forUser($gerente)->allows('viewAny', Usuario::class));
        $this->assertFalse(Gate::forUser($gerente)->allows('create', Usuario::class));
        $this->assertFalse(Gate::forUser($gerente)->allows('update', $admin));
        $this->assertFalse(Gate::forUser($gerente)->allows('cambiarEstado', $admin));
    }

    public function test_parametro_global_policy_allows_read_to_admin_and_gerente_and_write_only_to_admin(): void
    {
        // Arrange
        $admin = $this->usuarioConRol(Rol::Administrador);
        $gerente = $this->usuarioConRol(Rol::Gerente);
        $usuario = $this->usuarioConRol();

        // Act / Assert
        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', ParametroGlobal::class));
        $this->assertTrue(Gate::forUser($gerente)->allows('viewAny', ParametroGlobal::class));
        $this->assertFalse(Gate::forUser($usuario)->allows('viewAny', ParametroGlobal::class));

        $this->assertTrue(Gate::forUser($admin)->allows('update', ParametroGlobal::class));
        $this->assertFalse(Gate::forUser($gerente)->allows('update', ParametroGlobal::class));
    }
}
