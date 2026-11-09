<?php

namespace Tests\Feature\Modules\Reportes;

use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\PermissionSeeder;
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
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);
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
            ->where('resumen.produccion.activas', 0)
            ->where('resumen.produccion.proximas_entregas', [])
            ->where('resumen.inventario', [
                'activos' => 0,
                'bajo_minimo' => 0,
                'comprometido' => 0,
            ])
            ->where('resumen.compras', [
                'pendientes_recepcion' => 0,
                'solicitudes_abiertas' => 0,
                'proveedores_activos' => 0,
            ])
            ->where('resumen.mantenimiento', [
                'maquinas_bloqueadas' => 0,
                'planes' => 0,
                'bloqueadas' => [],
            ])
        );
    }

    public function test_the_dashboard_only_sends_the_areas_the_actor_can_access(): void
    {
        // Arrange
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);
        $usuario = Usuario::factory()->create();
        $usuario->assignRole(Rol::SupervisorProduccion->value);

        // Act
        $response = $this->actingAs($usuario)->get('/dashboard');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->where('resumen.produccion.activas', 0)
            ->where('resumen.mantenimiento.planes', 0)
            ->where('resumen.inventario', null)
            ->where('resumen.compras', null)
        );
    }

    public function test_the_dashboard_sends_no_area_when_the_actor_has_no_permissions(): void
    {
        // Arrange
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);
        $usuario = Usuario::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->get('/dashboard');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->where('resumen', [
                'produccion' => null,
                'inventario' => null,
                'compras' => null,
                'mantenimiento' => null,
            ])
        );
    }
}
