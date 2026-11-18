<?php

namespace Tests\Feature\Modules\Usuarios;

use App\Modules\Usuarios\Enums\EstadoUsuario;
use App\Modules\Usuarios\Models\ParametroGlobal;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\ParametroGlobalSeeder;
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

    public function test_a_usuario_can_authenticate_with_a_different_email_case(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create([
            'email' => 'laura@confort.test',
            'clave_acceso' => 'secret-password',
        ]);

        // Act
        $response = $this->post('/login', [
            'email' => 'Laura@Confort.Test',
            'password' => 'secret-password',
        ]);

        // Assert
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($usuario);
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

    public function test_an_inhabilitado_usuario_cannot_authenticate(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create(['clave_acceso' => 'secret-password']);
        $usuario->estado = EstadoUsuario::Inhabilitado->value;
        $usuario->save();

        // Act
        $response = $this->from('/login')->post('/login', [
            'email' => $usuario->email,
            'password' => 'secret-password',
        ]);

        // Assert
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_an_inhabilitado_usuario_with_an_open_session_is_logged_out(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();
        $this->actingAs($usuario);
        $usuario->estado = EstadoUsuario::Inhabilitado->value;
        $usuario->save();

        // Act
        $response = $this->get('/dashboard');

        // Assert
        $response->assertRedirect('/login');
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

    public function test_an_array_email_returns_a_validation_error(): void
    {
        // Act
        $response = $this->post('/login', [
            'email' => ['a@b.test'],
            'password' => 'secret-password',
        ]);

        // Assert
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_the_login_lockout_threshold_is_configurable(): void
    {
        // Arrange
        $this->seed(ParametroGlobalSeeder::class);
        ParametroGlobal::where('nombre', 'seguridad_intentos_fallidos')->update(['valor' => '2']);
        $usuario = Usuario::factory()->create(['clave_acceso' => 'secret-password']);

        // Act
        $this->post('/login', ['email' => $usuario->email, 'password' => 'incorrecta']);
        $this->post('/login', ['email' => $usuario->email, 'password' => 'incorrecta']);
        $response = $this->post('/login', ['email' => $usuario->email, 'password' => 'secret-password']);

        // Assert
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_a_session_expires_after_the_configured_idle_limit(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();
        $this->actingAs($usuario)->get('/dashboard')->assertOk();

        // Act
        $this->travel(31)->minutes();
        $response = $this->get('/dashboard');

        // Assert
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_a_session_inside_the_idle_limit_stays_open(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();
        $this->actingAs($usuario)->get('/dashboard')->assertOk();

        // Act
        $this->travel(5)->minutes();

        // Assert
        $this->get('/dashboard')->assertOk();
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
