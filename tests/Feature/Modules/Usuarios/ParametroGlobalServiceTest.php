<?php

namespace Tests\Feature\Modules\Usuarios;

use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\ParametroGlobalService;
use Database\Seeders\ParametroGlobalSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ParametroGlobalServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_parametro_returns_the_stored_value(): void
    {
        // Arrange
        $this->seed(ParametroGlobalSeeder::class);
        $service = new ParametroGlobalService;

        // Act
        $valor = $service->parametro('compras_vigencia_cotizacion_dias');

        // Assert
        $this->assertSame('7', $valor);
    }

    public function test_parametro_returns_null_when_the_key_does_not_exist(): void
    {
        // Arrange
        $service = new ParametroGlobalService;

        // Act
        $valor = $service->parametro('no_existe');

        // Assert
        $this->assertNull($valor);
    }

    public function test_definir_parametro_requires_administrador(): void
    {
        // Arrange
        Role::create(['name' => Rol::Gerente->value, 'guard_name' => 'web']);
        $usuario = Usuario::factory()->create();
        $usuario->assignRole(Rol::Gerente->value);
        $service = new ParametroGlobalService;

        // Act / Assert
        $this->expectException(AuthorizationException::class);
        $service->definirParametro($usuario, 'umbral_tolerancia_precio', '20');
    }

    public function test_administrador_can_define_a_parametro(): void
    {
        // Arrange
        Role::create(['name' => Rol::Administrador->value, 'guard_name' => 'web']);
        $admin = Usuario::factory()->create();
        $admin->assignRole(Rol::Administrador->value);
        $service = new ParametroGlobalService;

        // Act
        $parametro = $service->definirParametro($admin, 'umbral_tolerancia_precio', '20', 'Compras');

        // Assert
        $this->assertSame('20', $parametro->valor);
        $this->assertSame('20', $service->parametro('umbral_tolerancia_precio'));
        $this->assertDatabaseHas('parametros_globales', [
            'nombre' => 'umbral_tolerancia_precio',
            'valor' => '20',
        ]);
    }
}
