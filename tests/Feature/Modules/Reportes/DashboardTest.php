<?php

namespace Tests\Feature\Modules\Reportes;

use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_screen(): void
    {
        // Act
        $response = $this->get('/dashboard');

        // Assert
        $response->assertRedirect('/login');
    }

    public function test_authenticated_usuarios_can_visit_the_dashboard(): void
    {
        // Arrange
        $this->seed(RoleSeeder::class);
        $usuario = Usuario::factory()->create();
        $usuario->assignRole(Rol::Administrador->value);

        // Act
        $response = $this->actingAs($usuario)->get('/dashboard');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->where('auth.user.email', $usuario->email)
            ->where('auth.user.roles', [Rol::Administrador->value])
        );
    }
}
