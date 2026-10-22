<?php

namespace Tests\Feature\Modules\Usuarios;

use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UsuarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_factory_persists_a_usuario_with_a_hashed_password(): void
    {
        // Arrange
        $plainPassword = 'secret-password';

        // Act
        $usuario = Usuario::factory()->create(['password' => $plainPassword]);

        // Assert
        $this->assertDatabaseHas('usuarios', ['email' => $usuario->email]);
        $this->assertNotSame($plainPassword, $usuario->password);
        $this->assertTrue(Hash::check($plainPassword, $usuario->password));
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
}
