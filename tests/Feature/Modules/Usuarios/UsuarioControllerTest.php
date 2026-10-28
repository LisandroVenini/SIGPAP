<?php

namespace Tests\Feature\Modules\Usuarios;

use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class UsuarioControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Usuario
    {
        $this->seed(RoleSeeder::class);
        $admin = Usuario::factory()->create(['nombre' => 'Admin Sistema', 'email' => 'admin@test.local']);
        $admin->assignRole(Rol::Administrador->value);

        return $admin;
    }

    public function test_guests_are_redirected_to_the_login_screen(): void
    {
        // Act
        $response = $this->get('/usuarios');

        // Assert
        $response->assertRedirect('/login');
    }

    public function test_usuarios_without_the_administrador_role_are_forbidden(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->get('/usuarios');

        // Assert
        $response->assertForbidden();
    }

    public function test_an_administrador_can_list_usuarios(): void
    {
        // Arrange
        $admin = $this->admin();

        // Act
        $response = $this->actingAs($admin)->get('/usuarios');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Usuarios/Index')
            ->has('usuarios.data')
            ->has('roles')
            ->has('resumen')
        );
    }

    public function test_an_administrador_can_create_a_usuario(): void
    {
        // Arrange
        $admin = $this->admin();

        // Act
        $response = $this->actingAs($admin)->post('/usuarios', [
            'nombre' => 'Laura Cáceres',
            'email' => 'laura@confort.test',
            'password' => 'secret-password',
            'rol' => Rol::Gerente->value,
        ]);

        // Assert
        $response->assertRedirect('/usuarios');
        $usuario = Usuario::where('email', 'laura@confort.test')->firstOrFail();
        $this->assertTrue($usuario->hasRole(Rol::Gerente->value));
        $this->assertDatabaseHas('eventos_de_auditoria', ['accion' => 'crear_usuario']);
    }

    public function test_a_duplicate_email_is_rejected(): void
    {
        // Arrange
        $admin = $this->admin();
        Usuario::factory()->create(['email' => 'laura@confort.test']);

        // Act
        $response = $this->actingAs($admin)->post('/usuarios', [
            'nombre' => 'Otra Persona',
            'email' => 'laura@confort.test',
            'password' => 'secret-password',
            'rol' => Rol::Gerente->value,
        ]);

        // Assert
        $response->assertSessionHasErrors('email');
    }

    public function test_an_administrador_can_update_a_usuario(): void
    {
        // Arrange
        $admin = $this->admin();
        $usuario = Usuario::factory()->create(['email' => 'usuario@confort.test']);
        $usuario->assignRole(Rol::Gerente->value);

        // Act
        $response = $this->actingAs($admin)->put("/usuarios/{$usuario->id}", [
            'nombre' => 'Actualizado',
            'email' => 'usuario@confort.test',
            'password' => '',
            'rol' => Rol::EncargadoStock->value,
            'estado' => 'Inhabilitado',
        ]);

        // Assert
        $response->assertRedirect('/usuarios');
        $usuario->refresh();
        $this->assertSame('Inhabilitado', $usuario->estado);
        $this->assertTrue($usuario->hasRole(Rol::EncargadoStock->value));
    }

    public function test_an_administrador_can_toggle_a_usuario_state(): void
    {
        // Arrange
        $admin = $this->admin();
        $usuario = Usuario::factory()->create();

        // Act
        $response = $this->actingAs($admin)->patch("/usuarios/{$usuario->id}/estado");

        // Assert
        $response->assertRedirect('/usuarios');
        $this->assertSame('Inhabilitado', $usuario->refresh()->estado);
    }

    public function test_an_administrador_cannot_inhabilitate_self(): void
    {
        // Arrange
        $admin = $this->admin();

        // Act
        $response = $this->actingAs($admin)->patch("/usuarios/{$admin->id}/estado");

        // Assert
        $response->assertSessionHasErrors('estado');
        $this->assertSame('Activo', $admin->refresh()->estado);
    }

    public function test_an_administrador_cannot_inhabilitate_self_via_update(): void
    {
        // Arrange
        $admin = $this->admin();

        // Act
        $response = $this->actingAs($admin)->put("/usuarios/{$admin->id}", [
            'nombre' => $admin->nombre,
            'email' => $admin->email,
            'password' => '',
            'rol' => Rol::Administrador->value,
            'estado' => 'Inhabilitado',
        ]);

        // Assert
        $response->assertSessionHasErrors('estado');
        $this->assertSame('Activo', $admin->refresh()->estado);
    }

    public function test_an_administrador_cannot_remove_their_own_administrador_role(): void
    {
        // Arrange
        $admin = $this->admin();

        // Act
        $response = $this->actingAs($admin)->put("/usuarios/{$admin->id}", [
            'nombre' => $admin->nombre,
            'email' => $admin->email,
            'password' => '',
            'rol' => Rol::Gerente->value,
            'estado' => 'Activo',
        ]);

        // Assert
        $response->assertSessionHasErrors('rol');
        $this->assertTrue($admin->refresh()->hasRole(Rol::Administrador->value));
    }
}
