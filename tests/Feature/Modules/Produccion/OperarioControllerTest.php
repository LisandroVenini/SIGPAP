<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use App\Modules\Produccion\Models\Operario;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class OperarioControllerTest extends TestCase
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
        $response = $this->get('/produccion/operarios');

        // Assert
        $response->assertRedirect('/login');
    }

    public function test_usuarios_without_role_are_forbidden(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->get('/produccion/operarios');

        // Assert
        $response->assertForbidden();
    }

    public function test_a_supervisor_can_list_operarios(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        Operario::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->get('/produccion/operarios');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Produccion/Operarios/Index')
            ->has('operarios.data')
            ->has('filtros'));
    }

    public function test_a_supervisor_can_create_an_operario(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);

        // Act
        $response = $this->actingAs($usuario)->post('/produccion/operarios', [
            'nombre' => 'Ana',
            'apellido' => 'Pérez',
            'especialidad' => 'Pintura',
        ]);

        // Assert
        $response->assertRedirect('/produccion/operarios');
        $this->assertDatabaseHas('operarios', ['nombre' => 'Ana', 'apellido' => 'Pérez']);
    }

    public function test_a_supervisor_can_update_an_operario(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $operario = Operario::factory()->create(['especialidad' => 'Pintura']);

        // Act
        $response = $this->actingAs($usuario)->put("/produccion/operarios/{$operario->id}", [
            'nombre' => $operario->nombre,
            'apellido' => $operario->apellido,
            'especialidad' => 'Lijado',
        ]);

        // Assert
        $response->assertRedirect('/produccion/operarios');
        $this->assertDatabaseHas('operarios', ['id' => $operario->id, 'especialidad' => 'Lijado']);
    }

    public function test_a_supervisor_can_give_baja_to_an_unassigned_operario(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $operario = Operario::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->delete("/produccion/operarios/{$operario->id}");

        // Assert
        $response->assertRedirect('/produccion/operarios');
        $this->assertSoftDeleted('operarios', ['id' => $operario->id]);
    }

    public function test_giving_baja_to_an_operario_in_an_order_in_progress_returns_an_error(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $operario = Operario::factory()->create();
        $orden = OrdenDeProduccion::factory()->create(['estado' => EstadoOrdenProduccion::EnCurso->value]);
        $operario->ordenesDeProduccion()->attach($orden);

        // Act
        $response = $this->actingAs($usuario)
            ->from('/produccion/operarios')
            ->delete("/produccion/operarios/{$operario->id}");

        // Assert
        $response->assertRedirect('/produccion/operarios');
        $response->assertSessionHasErrors('operario');
        $this->assertNotSoftDeleted('operarios', ['id' => $operario->id]);
    }
}
