<?php

namespace Tests\Feature\Modules\Usuarios;

use App\Modules\Usuarios\Enums\Rol;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_auditoria_consultar_is_granted_to_administrador_and_gerente(): void
    {
        // Arrange
        $this->seed(RoleSeeder::class);

        // Act
        $this->seed(PermissionSeeder::class);

        // Assert
        $this->assertTrue(Role::findByName(Rol::Administrador->value, 'web')->hasPermissionTo('auditoria.consultar'));
        $this->assertTrue(Role::findByName(Rol::Gerente->value, 'web')->hasPermissionTo('auditoria.consultar'));
        $this->assertFalse(Role::findByName(Rol::EncargadoCompras->value, 'web')->hasPermissionTo('auditoria.consultar'));
    }
}
