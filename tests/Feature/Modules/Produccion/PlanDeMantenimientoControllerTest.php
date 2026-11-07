<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Produccion\Enums\EstadoMaquinaria;
use App\Modules\Produccion\Models\Maquinaria;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Factories\PlanDeMantenimientoFactory;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PlanDeMantenimientoControllerTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioConRol(Rol $rol): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole($rol->value);

        return $usuario;
    }

    public function test_guests_are_redirected_to_login(): void
    {
        // Act
        $response = $this->get('/produccion/planes-de-mantenimiento');

        // Assert
        $response->assertRedirect('/login');
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->get('/produccion/planes-de-mantenimiento');

        // Assert
        $response->assertForbidden();
    }

    public function test_supervisor_can_list_plans(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $maquinaria = Maquinaria::factory()->create();
        PlanDeMantenimientoFactory::new()->create(['maquinaria_id' => $maquinaria->id]);

        // Act
        $response = $this->actingAs($usuario)->get('/produccion/planes-de-mantenimiento');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Produccion/Mantenimiento/Index')
            ->has('planes.data')
            ->has('intervenciones')
            ->has('maquinarias')
            ->has('filtros'));
    }

    public function test_supervisor_can_create_a_plan(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $maquinaria = Maquinaria::factory()->create();

        // Act
        $response = $this->actingAs($usuario)
            ->from('/produccion/planes-de-mantenimiento')
            ->post('/produccion/planes-de-mantenimiento', [
                'maquinaria_id' => $maquinaria->id,
                'umbral_uso' => 1200,
                'descripcion' => 'Revisión anual',
            ]);

        // Assert
        $response->assertRedirect('/produccion/planes-de-mantenimiento');
        $response->assertSessionHas('success', 'Plan de mantenimiento creado.');
        $this->assertDatabaseHas('planes_de_mantenimiento', [
            'maquinaria_id' => $maquinaria->id,
            'umbral_uso' => 1200,
            'descripcion' => 'Revisión anual',
        ]);
    }

    public function test_supervisor_can_update_a_plan(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $maquinaria = Maquinaria::factory()->create();
        $plan = PlanDeMantenimientoFactory::new()->create(['maquinaria_id' => $maquinaria->id]);

        // Act
        $response = $this->actingAs($usuario)
            ->from('/produccion/planes-de-mantenimiento')
            ->put("/produccion/planes-de-mantenimiento/{$plan->id}", [
                'maquinaria_id' => $maquinaria->id,
                'umbral_uso' => 2500,
                'descripcion' => 'Revisión semestral',
            ]);

        // Assert
        $response->assertRedirect('/produccion/planes-de-mantenimiento');
        $response->assertSessionHas('success', 'Plan de mantenimiento actualizado.');
        $this->assertDatabaseHas('planes_de_mantenimiento', [
            'id' => $plan->id,
            'umbral_uso' => 2500,
            'descripcion' => 'Revisión semestral',
        ]);
    }

    public function test_supervisor_can_delete_a_plan(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $maquinaria = Maquinaria::factory()->create([
            'estado_operativo' => EstadoMaquinaria::Disponible->value,
        ]);
        $plan = PlanDeMantenimientoFactory::new()->create(['maquinaria_id' => $maquinaria->id]);

        // Act
        $response = $this->actingAs($usuario)
            ->from('/produccion/planes-de-mantenimiento')
            ->delete("/produccion/planes-de-mantenimiento/{$plan->id}");

        // Assert
        $response->assertRedirect('/produccion/planes-de-mantenimiento');
        $response->assertSessionHas('success', 'Plan de mantenimiento eliminado.');
        $this->assertDatabaseMissing('planes_de_mantenimiento', ['id' => $plan->id]);
    }

    public function test_deleting_a_plan_for_blocked_machine_returns_session_error(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $maquinaria = Maquinaria::factory()->create([
            'estado_operativo' => EstadoMaquinaria::Bloqueada->value,
        ]);
        $plan = PlanDeMantenimientoFactory::new()->create(['maquinaria_id' => $maquinaria->id]);

        // Act
        $response = $this->actingAs($usuario)
            ->from('/produccion/planes-de-mantenimiento')
            ->delete("/produccion/planes-de-mantenimiento/{$plan->id}");

        // Assert
        $response->assertRedirect('/produccion/planes-de-mantenimiento');
        $response->assertSessionHasErrors('plan');
        $this->assertDatabaseHas('planes_de_mantenimiento', ['id' => $plan->id]);
    }

    public function test_duplicate_machine_plan_is_rejected(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $maquinaria = Maquinaria::factory()->create();
        PlanDeMantenimientoFactory::new()->create(['maquinaria_id' => $maquinaria->id]);

        // Act
        $response = $this->actingAs($usuario)
            ->from('/produccion/planes-de-mantenimiento')
            ->post('/produccion/planes-de-mantenimiento', [
                'maquinaria_id' => $maquinaria->id,
                'umbral_uso' => 500,
                'descripcion' => 'Segundo plan',
            ]);

        // Assert
        $response->assertRedirect('/produccion/planes-de-mantenimiento');
        $response->assertSessionHasErrors('maquinaria_id');
        $this->assertDatabaseCount('planes_de_mantenimiento', 1);
    }
}
