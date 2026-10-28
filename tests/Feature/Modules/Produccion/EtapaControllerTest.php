<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Produccion\Models\AsignacionDeEtapa;
use App\Modules\Produccion\Models\Etapa;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class EtapaControllerTest extends TestCase
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
        $response = $this->get('/produccion/etapas');

        // Assert
        $response->assertRedirect('/login');
    }

    public function test_usuarios_without_role_are_forbidden(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->get('/produccion/etapas');

        // Assert
        $response->assertForbidden();
    }

    public function test_a_supervisor_can_list_etapas(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        Etapa::factory()->create(['nombre' => 'Lijado']);

        // Act
        $response = $this->actingAs($usuario)->get('/produccion/etapas');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Produccion/Etapas/Index')
            ->has('etapas.data')
            ->has('filtros'));
    }

    public function test_a_supervisor_can_create_an_etapa(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);

        // Act
        $response = $this->actingAs($usuario)->post('/produccion/etapas', [
            'nombre' => 'Lijado',
            'descripcion' => 'Lijado de madera',
        ]);

        // Assert
        $response->assertRedirect('/produccion/etapas');
        $this->assertDatabaseHas('etapas', ['nombre' => 'Lijado']);
    }

    public function test_a_duplicate_name_is_rejected_case_insensitively(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        Etapa::factory()->create(['nombre' => 'Lijado']);

        // Act
        $response = $this->actingAs($usuario)->post('/produccion/etapas', [
            'nombre' => 'lijado',
        ]);

        // Assert
        $response->assertSessionHasErrors('nombre');
        $this->assertDatabaseCount('etapas', 1);
    }

    public function test_a_supervisor_can_update_an_etapa_keeping_its_own_name(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $etapa = Etapa::factory()->create(['nombre' => 'Lijado']);

        // Act
        $response = $this->actingAs($usuario)->put("/produccion/etapas/{$etapa->id}", [
            'nombre' => 'Lijado',
            'descripcion' => 'Actualizada',
        ]);

        // Assert
        $response->assertRedirect('/produccion/etapas');
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('etapas', ['id' => $etapa->id, 'descripcion' => 'Actualizada']);
    }

    public function test_updating_to_another_etapa_name_is_rejected_case_insensitively(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        Etapa::factory()->create(['nombre' => 'Pintado']);
        $etapa = Etapa::factory()->create(['nombre' => 'Lijado']);

        // Act
        $response = $this->actingAs($usuario)->put("/produccion/etapas/{$etapa->id}", [
            'nombre' => 'pintado',
        ]);

        // Assert
        $response->assertSessionHasErrors('nombre');
        $this->assertDatabaseHas('etapas', ['id' => $etapa->id, 'nombre' => 'Lijado']);
    }

    public function test_an_etapa_name_can_be_reused_after_a_soft_delete(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $etapa = Etapa::factory()->create(['nombre' => 'Lijado']);

        // Act
        $this->actingAs($usuario)->delete("/produccion/etapas/{$etapa->id}");
        $response = $this->actingAs($usuario)->post('/produccion/etapas', ['nombre' => 'Lijado']);

        // Assert
        $response->assertRedirect('/produccion/etapas');
        $response->assertSessionHasNoErrors();
        $this->assertSame(2, Etapa::withTrashed()->where('nombre', 'Lijado')->count());
    }

    public function test_a_supervisor_can_update_an_etapa(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $etapa = Etapa::factory()->create(['nombre' => 'Lijado']);

        // Act
        $response = $this->actingAs($usuario)->put("/produccion/etapas/{$etapa->id}", [
            'nombre' => 'Lijado fino',
            'descripcion' => null,
        ]);

        // Assert
        $response->assertRedirect('/produccion/etapas');
        $this->assertDatabaseHas('etapas', ['id' => $etapa->id, 'nombre' => 'Lijado fino']);
    }

    public function test_a_supervisor_can_delete_an_unassigned_etapa(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $etapa = Etapa::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->delete("/produccion/etapas/{$etapa->id}");

        // Assert
        $response->assertRedirect('/produccion/etapas');
        $this->assertSoftDeleted('etapas', ['id' => $etapa->id]);
    }

    public function test_deleting_an_assigned_etapa_returns_an_error(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $etapa = Etapa::factory()->create();
        AsignacionDeEtapa::factory()->create(['etapa_id' => $etapa->id]);

        // Act
        $response = $this->actingAs($usuario)
            ->from('/produccion/etapas')
            ->delete("/produccion/etapas/{$etapa->id}");

        // Assert
        $response->assertRedirect('/produccion/etapas');
        $response->assertSessionHasErrors('etapa');
        $this->assertNotSoftDeleted('etapas', ['id' => $etapa->id]);
    }
}
