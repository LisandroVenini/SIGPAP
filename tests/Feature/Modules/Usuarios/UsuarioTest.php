<?php

namespace Tests\Feature\Modules\Usuarios;

use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UsuarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_factory_persists_a_usuario_with_a_hashed_password(): void
    {
        // Arrange
        $plainPassword = 'secret-password';

        // Act
        $usuario = Usuario::factory()->create(['clave_acceso' => $plainPassword]);

        // Assert
        $this->assertDatabaseHas('usuarios', ['email' => $usuario->email]);
        $this->assertNotSame($plainPassword, $usuario->clave_acceso);
        $this->assertTrue(Hash::check($plainPassword, $usuario->clave_acceso));
    }

    public function test_the_auth_provider_authenticates_a_usuario(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();

        // Act
        $this->actingAs($usuario);

        // Assert
        $this->assertSame(Usuario::class, config('auth.providers.users.model'));
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_it_authenticates_with_the_domain_password_column(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create(['clave_acceso' => 'secret-password']);

        // Act
        $authenticated = Auth::attempt([
            'email' => $usuario->email,
            'password' => 'secret-password',
        ]);

        // Assert
        $this->assertTrue($authenticated);
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_it_rejects_an_invalid_password(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create(['clave_acceso' => 'secret-password']);

        // Act
        $authenticated = Auth::attempt([
            'email' => $usuario->email,
            'password' => 'wrong-password',
        ]);

        // Assert
        $this->assertFalse($authenticated);
        $this->assertGuest();
    }

    public function test_it_resolves_the_usuario_morph_alias(): void
    {
        // Arrange
        $usuario = Usuario::factory()->make();

        // Act
        $alias = $usuario->getMorphClass();

        // Assert
        $this->assertSame('usuario', $alias);
    }

    public function test_it_assigns_a_role_to_a_usuario(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();
        $role = Role::create(['name' => 'Administrador', 'guard_name' => 'web']);

        // Act
        $usuario->assignRole($role);

        // Assert
        $this->assertTrue($usuario->hasRole('Administrador'));
        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => $role->id,
            'model_id' => $usuario->id,
            'model_type' => 'usuario',
        ]);
    }
}
