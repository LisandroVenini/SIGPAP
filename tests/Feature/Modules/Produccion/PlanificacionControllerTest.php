<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Models\PlanificacionDeProduccion;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PlanificacionControllerTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioConRol(Rol $rol): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole($rol->value);

        return $usuario;
    }

    public function test_guests_are_redirected_to_the_login_screen(): void
    {
        // Act
        $response = $this->get('/produccion/planificaciones');

        // Assert
        $response->assertRedirect('/login');
    }

    public function test_usuarios_without_role_are_forbidden(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->get('/produccion/planificaciones');

        // Assert
        $response->assertForbidden();
    }

    public function test_a_supervisor_can_list_planificaciones(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        PlanificacionDeProduccion::factory()->create(['nombre' => 'Semana 12']);

        // Act
        $response = $this->actingAs($usuario)->get('/produccion/planificaciones');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Produccion/Planificaciones/Index')
            ->has('planificaciones.data')
            ->has('filtros'));
    }

    public function test_a_supervisor_can_create_a_planificacion(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);

        // Act
        $response = $this->actingAs($usuario)->post('/produccion/planificaciones', [
            'nombre' => 'Semana 12',
            'fecha_inicio' => now()->addDay()->toDateString(),
            'fecha_fin' => now()->addDays(7)->toDateString(),
            'observaciones' => 'Turno completo',
        ]);

        // Assert
        $response->assertRedirect('/produccion/planificaciones');
        $this->assertDatabaseHas('planificaciones_de_produccion', ['nombre' => 'Semana 12']);
    }

    public function test_a_period_that_ends_before_it_starts_is_rejected(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);

        // Act
        $response = $this->actingAs($usuario)->post('/produccion/planificaciones', [
            'nombre' => 'Semana invertida',
            'fecha_inicio' => now()->addDays(10)->toDateString(),
            'fecha_fin' => now()->addDays(2)->toDateString(),
        ]);

        // Assert
        $response->assertSessionHasErrors('fecha_fin');
        $this->assertDatabaseCount('planificaciones_de_produccion', 0);
    }

    public function test_a_supervisor_can_update_a_planificacion(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $planificacion = PlanificacionDeProduccion::factory()->create(['nombre' => 'Semana 12']);

        // Act
        $response = $this->actingAs($usuario)->put("/produccion/planificaciones/{$planificacion->id}", [
            'nombre' => 'Semana 12 ajustada',
            'fecha_inicio' => now()->addDay()->toDateString(),
            'fecha_fin' => now()->addDays(9)->toDateString(),
            'observaciones' => null,
        ]);

        // Assert
        $response->assertRedirect('/produccion/planificaciones');
        $this->assertDatabaseHas('planificaciones_de_produccion', [
            'id' => $planificacion->id,
            'nombre' => 'Semana 12 ajustada',
        ]);
    }

    public function test_a_supervisor_can_delete_a_planificacion_without_ordenes(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $planificacion = PlanificacionDeProduccion::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->delete("/produccion/planificaciones/{$planificacion->id}");

        // Assert
        $response->assertRedirect('/produccion/planificaciones');
        $this->assertDatabaseMissing('planificaciones_de_produccion', ['id' => $planificacion->id]);
    }

    public function test_deleting_a_planificacion_with_ordenes_returns_an_error(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $planificacion = PlanificacionDeProduccion::factory()->create();
        OrdenDeProduccion::factory()->create(['planificacion_de_produccion_id' => $planificacion->id]);

        // Act
        $response = $this->actingAs($usuario)
            ->from('/produccion/planificaciones')
            ->delete("/produccion/planificaciones/{$planificacion->id}");

        // Assert
        $response->assertRedirect('/produccion/planificaciones');
        $response->assertSessionHasErrors('planificacion');
        $this->assertDatabaseHas('planificaciones_de_produccion', ['id' => $planificacion->id]);
    }

    public function test_a_start_date_before_today_is_rejected(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);

        // Act
        $response = $this->actingAs($usuario)->post('/produccion/planificaciones', [
            'nombre' => 'Semana pasada',
            'fecha_inicio' => now()->subDay()->toDateString(),
            'fecha_fin' => now()->addDays(5)->toDateString(),
        ]);

        // Assert
        $response->assertSessionHasErrors('fecha_inicio');
        $this->assertDatabaseCount('planificaciones_de_produccion', 0);
    }

    public function test_an_end_date_before_today_is_rejected(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);

        // Act
        $response = $this->actingAs($usuario)->post('/produccion/planificaciones', [
            'nombre' => 'Semana vieja',
            'fecha_inicio' => now()->subDays(10)->toDateString(),
            'fecha_fin' => now()->subDay()->toDateString(),
        ]);

        // Assert
        $response->assertSessionHasErrors(['fecha_inicio', 'fecha_fin']);
        $this->assertDatabaseCount('planificaciones_de_produccion', 0);
    }

    public function test_a_historical_planificacion_can_be_edited_without_moving_its_dates(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $planificacion = PlanificacionDeProduccion::factory()->create([
            'nombre' => 'Semana pasada',
            'fecha_inicio' => now()->subDays(14)->toDateString(),
            'fecha_fin' => now()->subDays(8)->toDateString(),
        ]);

        // Act
        $response = $this->actingAs($usuario)->put("/produccion/planificaciones/{$planificacion->id}", [
            'nombre' => 'Semana pasada (corregida)',
            'fecha_inicio' => now()->subDays(14)->toDateString(),
            'fecha_fin' => now()->subDays(8)->toDateString(),
            'observaciones' => 'Se corrigió el nombre.',
        ]);

        // Assert
        $response->assertRedirect('/produccion/planificaciones');
        $this->assertDatabaseHas('planificaciones_de_produccion', [
            'id' => $planificacion->id,
            'nombre' => 'Semana pasada (corregida)',
        ]);
    }
}
