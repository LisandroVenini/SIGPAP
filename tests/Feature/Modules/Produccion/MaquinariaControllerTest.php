<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use App\Modules\Produccion\Models\Maquinaria;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Factories\IntervencionDeMantenimientoFactory;
use Database\Factories\PlanDeMantenimientoFactory;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class MaquinariaControllerTest extends TestCase
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
        $response = $this->get('/produccion/maquinarias');

        // Assert
        $response->assertRedirect('/login');
    }

    public function test_users_without_role_are_forbidden(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->get('/produccion/maquinarias');

        // Assert
        $response->assertForbidden();
    }

    public function test_a_supervisor_can_list_machines(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        Maquinaria::factory()->create(['nombre' => 'Lijadora orbital']);

        // Act
        $response = $this->actingAs($usuario)->get('/produccion/maquinarias');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Produccion/Maquinarias/Index')
            ->has('maquinarias.data')
            ->has('filtros'));
    }

    public function test_a_supervisor_can_create_a_machine(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);

        // Act
        $response = $this->actingAs($usuario)->post('/produccion/maquinarias', [
            'nombre' => 'Lijadora orbital',
            'tipo' => 'Lijado',
        ]);

        // Assert
        $response->assertRedirect('/produccion/maquinarias');
        $this->assertDatabaseHas('maquinarias', ['nombre' => 'Lijadora orbital', 'tipo' => 'Lijado']);
    }

    public function test_duplicate_names_are_allowed(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        Maquinaria::factory()->create(['nombre' => 'Lijadora orbital']);

        // Act
        $response = $this->actingAs($usuario)->post('/produccion/maquinarias', [
            'nombre' => 'Lijadora orbital',
            'tipo' => 'Lijado',
        ]);

        // Assert
        $response->assertRedirect('/produccion/maquinarias');
        $this->assertDatabaseCount('maquinarias', 2);
    }

    public function test_a_supervisor_can_update_a_machine(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $maquinaria = Maquinaria::factory()->create(['nombre' => 'Lijadora']);

        // Act
        $response = $this->actingAs($usuario)->put("/produccion/maquinarias/{$maquinaria->id}", [
            'nombre' => 'Lijadora renovada',
            'tipo' => 'Acabado',
        ]);

        // Assert
        $response->assertRedirect('/produccion/maquinarias');
        $this->assertDatabaseHas('maquinarias', ['id' => $maquinaria->id, 'nombre' => 'Lijadora renovada', 'tipo' => 'Acabado']);
    }

    public function test_a_supervisor_can_delete_an_unrestricted_machine(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $maquinaria = Maquinaria::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->delete("/produccion/maquinarias/{$maquinaria->id}");

        // Assert
        $response->assertRedirect('/produccion/maquinarias');
        $this->assertDatabaseMissing('maquinarias', ['id' => $maquinaria->id]);
    }

    public function test_deleting_a_machine_with_an_in_progress_order_returns_a_session_error(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $maquinaria = Maquinaria::factory()->create();
        $orden = OrdenDeProduccion::factory()->create(['estado' => EstadoOrdenProduccion::EnCurso->value]);
        $orden->maquinarias()->attach($maquinaria->id);

        // Act
        $response = $this->actingAs($usuario)
            ->from('/produccion/maquinarias')
            ->delete("/produccion/maquinarias/{$maquinaria->id}");

        // Assert
        $response->assertRedirect('/produccion/maquinarias');
        $response->assertSessionHasErrors('maquinaria');
        $this->assertDatabaseHas('maquinarias', ['id' => $maquinaria->id]);
    }

    public function test_deleting_a_machine_with_maintenance_plans_returns_a_session_error(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $maquinaria = Maquinaria::factory()->create();
        PlanDeMantenimientoFactory::new()->create(['maquinaria_id' => $maquinaria->id]);

        // Act
        $response = $this->actingAs($usuario)
            ->from('/produccion/maquinarias')
            ->delete("/produccion/maquinarias/{$maquinaria->id}");

        // Assert
        $response->assertRedirect('/produccion/maquinarias');
        $response->assertSessionHasErrors('maquinaria');
        $this->assertDatabaseHas('maquinarias', ['id' => $maquinaria->id]);
    }

    public function test_deleting_a_machine_with_interventions_returns_a_session_error(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $maquinaria = Maquinaria::factory()->create();
        IntervencionDeMantenimientoFactory::new()->create(['maquinaria_id' => $maquinaria->id]);

        // Act
        $response = $this->actingAs($usuario)
            ->from('/produccion/maquinarias')
            ->delete("/produccion/maquinarias/{$maquinaria->id}");

        // Assert
        $response->assertRedirect('/produccion/maquinarias');
        $response->assertSessionHasErrors('maquinaria');
        $this->assertDatabaseHas('maquinarias', ['id' => $maquinaria->id]);
    }
}
