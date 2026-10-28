<?php

namespace Tests\Feature\Modules\Usuarios;

use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\UsuariosService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UsuariosServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_autorizar_returns_true_when_the_user_holds_the_permission(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();
        $permission = Permission::create(['name' => 'inventario.ver', 'guard_name' => 'web']);
        $usuario->givePermissionTo($permission);
        $service = new UsuariosService;

        // Act
        $autorizado = $service->autorizar($usuario, 'inventario.ver');

        // Assert
        $this->assertTrue($autorizado);
    }

    public function test_autorizar_returns_false_when_the_user_lacks_the_permission(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();
        $service = new UsuariosService;

        // Act
        $autorizado = $service->autorizar($usuario, 'compras.aprobar');

        // Assert
        $this->assertFalse($autorizado);
    }
}
