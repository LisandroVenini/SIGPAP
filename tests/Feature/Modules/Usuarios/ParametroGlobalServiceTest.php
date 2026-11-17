<?php

namespace Tests\Feature\Modules\Usuarios;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\ParametroGlobalService;
use Database\Seeders\ParametroGlobalSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ParametroGlobalServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_parametro_returns_the_stored_value(): void
    {
        // Arrange
        $this->seed(ParametroGlobalSeeder::class);
        $service = new ParametroGlobalService(new AuditoriaService);

        // Act
        $valor = $service->parametro('compras_vigencia_cotizacion_dias');

        // Assert
        $this->assertSame('7', $valor);
    }

    public function test_parametro_returns_null_when_the_key_does_not_exist(): void
    {
        // Arrange
        $service = new ParametroGlobalService(new AuditoriaService);

        // Act
        $valor = $service->parametro('no_existe');

        // Assert
        $this->assertNull($valor);
    }

    public function test_actualizar_persists_and_audits_each_parameter(): void
    {
        // Arrange
        $this->seed(ParametroGlobalSeeder::class);
        Role::create(['name' => Rol::Administrador->value, 'guard_name' => 'web']);
        $admin = Usuario::factory()->create();
        $admin->assignRole(Rol::Administrador->value);
        $service = new ParametroGlobalService(new AuditoriaService);

        // Act
        $service->actualizar($admin, ['compras_vigencia_cotizacion_dias' => '10'], '127.0.0.1');

        // Assert
        $this->assertSame('10', $service->parametro('compras_vigencia_cotizacion_dias'));
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'usuario_id' => $admin->id,
            'accion' => 'actualizar_parametro',
            'direccion_ip' => '127.0.0.1',
        ]);
    }

    public function test_actualizar_is_forbidden_for_a_non_administrador(): void
    {
        // Arrange
        $this->seed(ParametroGlobalSeeder::class);
        Role::create(['name' => Rol::Gerente->value, 'guard_name' => 'web']);
        $gerente = Usuario::factory()->create();
        $gerente->assignRole(Rol::Gerente->value);
        $service = new ParametroGlobalService(new AuditoriaService);

        // Act / Assert
        $this->expectException(AuthorizationException::class);
        $service->actualizar($gerente, ['compras_vigencia_cotizacion_dias' => '10']);
    }

    public function test_actualizar_rejects_an_unknown_key_and_rolls_back(): void
    {
        // Arrange
        $this->seed(ParametroGlobalSeeder::class);
        Role::create(['name' => Rol::Administrador->value, 'guard_name' => 'web']);
        $admin = Usuario::factory()->create();
        $admin->assignRole(Rol::Administrador->value);
        $service = new ParametroGlobalService(new AuditoriaService);

        // Act
        try {
            $service->actualizar($admin, [
                'compras_vigencia_cotizacion_dias' => '10',
                'no_existe' => 'x',
            ]);

            $this->fail('An unknown parameter should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('parametros', $exception->errors());
        }

        // Assert
        $this->assertNotSame('10', $service->parametro('compras_vigencia_cotizacion_dias'));
        $this->assertDatabaseCount('eventos_de_auditoria', 0);
    }
}
