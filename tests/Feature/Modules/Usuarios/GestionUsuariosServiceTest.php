<?php

namespace Tests\Feature\Modules\Usuarios;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Queries\UsuariosQuery;
use App\Modules\Usuarios\Services\GestionUsuariosService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GestionUsuariosServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): GestionUsuariosService
    {
        return new GestionUsuariosService(new AuditoriaService, new UsuariosQuery);
    }

    private function admin(): Usuario
    {
        $this->seed(RoleSeeder::class);
        $admin = Usuario::factory()->create(['nombre' => 'Admin Sistema', 'email' => 'admin@test.local']);
        $admin->assignRole(Rol::Administrador->value);

        return $admin;
    }

    public function test_crear_persists_the_usuario_with_role_and_audits(): void
    {
        // Arrange
        $admin = $this->admin();

        // Act
        $usuario = $this->service()->crear($admin, [
            'nombre' => 'Laura Cáceres',
            'email' => 'laura@confort.test',
            'password' => 'secret-password',
            'rol' => Rol::Gerente->value,
        ], '127.0.0.1');

        // Assert
        $this->assertDatabaseHas('usuarios', ['email' => 'laura@confort.test', 'estado' => 'Activo']);
        $this->assertTrue($usuario->hasRole(Rol::Gerente->value));
        $this->assertTrue(Hash::check('secret-password', $usuario->clave_acceso));
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'usuario_id' => $admin->id,
            'accion' => 'crear_usuario',
        ]);
    }

    public function test_actualizar_updates_role_password_and_state_and_audits(): void
    {
        // Arrange
        $admin = $this->admin();
        $usuario = Usuario::factory()->create(['clave_acceso' => 'old-password']);
        $usuario->assignRole(Rol::Gerente->value);

        // Act
        $this->service()->actualizar($admin, $usuario, [
            'nombre' => 'Nuevo Nombre',
            'email' => $usuario->email,
            'password' => 'new-password',
            'rol' => Rol::EncargadoStock->value,
            'estado' => 'Inhabilitado',
        ], '127.0.0.1');

        // Assert
        $usuario->refresh();
        $this->assertSame('Nuevo Nombre', $usuario->nombre);
        $this->assertSame('Inhabilitado', $usuario->estado);
        $this->assertTrue($usuario->hasRole(Rol::EncargadoStock->value));
        $this->assertTrue(Hash::check('new-password', $usuario->clave_acceso));
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'usuario_id' => $admin->id,
            'accion' => 'actualizar_usuario',
        ]);
    }

    public function test_alternar_estado_inhabilitates_and_audits(): void
    {
        // Arrange
        $admin = $this->admin();
        $usuario = Usuario::factory()->create();

        // Act
        $this->service()->alternarEstado($admin, $usuario, '127.0.0.1');

        // Assert
        $this->assertSame('Inhabilitado', $usuario->refresh()->estado);
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'usuario_id' => $admin->id,
            'accion' => 'cambiar_estado_usuario',
        ]);
    }

    public function test_an_administrador_cannot_inhabilitate_self(): void
    {
        // Arrange
        $admin = $this->admin();

        // Act / Assert
        $this->expectException(ValidationException::class);
        $this->service()->alternarEstado($admin, $admin);
    }

    public function test_an_inhabilitado_administrador_cannot_habilitate_self(): void
    {
        // Arrange
        $admin = $this->admin();
        $admin->estado = 'Inhabilitado';
        $admin->save();

        // Act / Assert
        $this->expectException(ValidationException::class);
        $this->service()->alternarEstado($admin, $admin);
    }

    public function test_an_administrador_can_habilitate_another_inhabilitado_usuario(): void
    {
        // Arrange
        $admin = $this->admin();
        $usuario = Usuario::factory()->create();
        $usuario->estado = 'Inhabilitado';
        $usuario->save();

        // Act
        $this->service()->alternarEstado($admin, $usuario, '127.0.0.1');

        // Assert
        $this->assertSame('Activo', $usuario->refresh()->estado);
    }

    public function test_listar_filters_by_search_and_role(): void
    {
        // Arrange
        $this->admin();
        $laura = Usuario::factory()->create(['nombre' => 'Laura Cáceres', 'email' => 'laura@confort.test']);
        $laura->assignRole(Rol::Gerente->value);
        Usuario::factory()->create(['nombre' => 'Pedro Gómez', 'email' => 'pedro@confort.test']);

        $service = $this->service();

        // Act / Assert
        $this->assertSame(1, $service->listar(['buscar' => 'Laura'])->total());
        $this->assertSame(1, $service->listar(['rol' => Rol::Gerente->value])->total());
        $this->assertSame(3, $service->listar()->total());
    }

    public function test_an_administrador_cannot_inhabilitate_self_via_update(): void
    {
        // Arrange
        $admin = $this->admin();

        // Act / Assert
        $this->expectException(ValidationException::class);
        $this->service()->actualizar($admin, $admin, [
            'nombre' => $admin->nombre,
            'email' => $admin->email,
            'password' => '',
            'rol' => Rol::Administrador->value,
            'estado' => 'Inhabilitado',
        ]);
    }

    public function test_an_administrador_cannot_remove_their_own_administrador_role(): void
    {
        // Arrange
        $admin = $this->admin();

        // Act / Assert
        $this->expectException(ValidationException::class);
        $this->service()->actualizar($admin, $admin, [
            'nombre' => $admin->nombre,
            'email' => $admin->email,
            'password' => '',
            'rol' => Rol::Gerente->value,
            'estado' => 'Activo',
        ]);
    }

    public function test_the_last_active_administrador_cannot_be_inhabilitated(): void
    {
        // Arrange
        $admin = $this->admin();
        $gerente = Usuario::factory()->create();
        $gerente->assignRole(Rol::Gerente->value);

        // Act / Assert
        try {
            $this->service()->alternarEstado($gerente, $admin, '127.0.0.1');

            $this->fail('The last active administrador should not be inhabilitated.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('estado', $exception->errors());
        }

        $this->assertSame('Activo', $admin->refresh()->estado);
    }

    public function test_the_last_active_administrador_cannot_lose_their_role(): void
    {
        // Arrange
        $admin = $this->admin();
        $gerente = Usuario::factory()->create();
        $gerente->assignRole(Rol::Gerente->value);

        // Act / Assert
        try {
            $this->service()->actualizar($gerente, $admin, [
                'nombre' => $admin->nombre,
                'email' => $admin->email,
                'password' => '',
                'rol' => Rol::Gerente->value,
                'estado' => 'Activo',
            ]);

            $this->fail('The last active administrador should keep the role.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('rol', $exception->errors());
        }

        $this->assertTrue($admin->refresh()->hasRole(Rol::Administrador->value));
    }

    public function test_an_administrador_can_be_inhabilitated_when_another_active_one_remains(): void
    {
        // Arrange
        $admin = $this->admin();
        $otroAdmin = Usuario::factory()->create();
        $otroAdmin->assignRole(Rol::Administrador->value);

        // Act
        $this->service()->alternarEstado($admin, $otroAdmin, '127.0.0.1');

        // Assert
        $this->assertSame('Inhabilitado', $otroAdmin->refresh()->estado);
    }
}
