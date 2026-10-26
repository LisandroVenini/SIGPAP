<?php

namespace Tests\Feature\Modules\Usuarios;

use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_login_screen_can_be_rendered(): void
    {
        // Act
        $response = $this->get('/login');

        // Assert
        $response->assertStatus(200);
    }

    public function test_a_usuario_can_authenticate_with_the_domain_password_column(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create(['clave_acceso' => 'secret-password']);

        // Act
        $response = $this->post('/login', [
            'email' => $usuario->email,
            'password' => 'secret-password',
        ]);

        // Assert
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($usuario);
        $this->get('/dashboard')->assertOk();
    }

    public function test_a_usuario_cannot_authenticate_with_an_invalid_password(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create(['clave_acceso' => 'secret-password']);

        // Act
        $response = $this->from('/login')->post('/login', [
            'email' => $usuario->email,
            'password' => 'wrong-password',
        ]);

        // Assert
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create(['clave_acceso' => 'secret-password']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', [
                'email' => $usuario->email,
                'password' => 'wrong-password',
            ]);
        }

        // Act
        $throttled = RateLimiter::tooManyAttempts(
            Str::transliterate(Str::lower($usuario->email).'|127.0.0.1'),
            5,
        );

        // Assert
        $this->assertTrue($throttled);
    }

    public function test_a_usuario_can_logout(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->post('/logout');

        // Assert
        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
