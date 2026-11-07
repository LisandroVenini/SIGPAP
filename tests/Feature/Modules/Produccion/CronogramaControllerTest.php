<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Inventario\Enums\EstadoArticulo;
use App\Modules\Inventario\Enums\OrigenArticulo;
use App\Modules\Inventario\Enums\TipoArticulo;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use App\Modules\Produccion\Enums\MotivoCambioPrioridad;
use App\Modules\Produccion\Models\AsignacionDeEtapa;
use App\Modules\Produccion\Models\Etapa;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Models\PlanificacionDeProduccion;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class CronogramaControllerTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioConRol(Rol $rol): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole($rol->value);

        return $usuario;
    }

    private function ordenConRuta(): OrdenDeProduccion
    {
        $producto = Articulo::factory()->create([
            'tipo' => TipoArticulo::ProductoTerminado->value,
            'origen' => OrigenArticulo::Fabricado->value,
            'estado' => EstadoArticulo::Activo->value,
        ]);

        AsignacionDeEtapa::factory()->create([
            'articulo_id' => $producto->id,
            'etapa_id' => Etapa::factory()->create()->id,
            'tiempo_por_unidad' => 60,
            'orden' => 1,
        ]);

        return OrdenDeProduccion::factory()->create([
            'articulo_id' => $producto->id,
            'planificacion_de_produccion_id' => PlanificacionDeProduccion::factory()->create()->id,
            'prioridad_actual' => 3,
            'fecha_entrega' => now()->addDays(30)->toDateString(),
        ]);
    }

    public function test_guests_are_redirected_to_the_login_screen(): void
    {
        // Act
        $response = $this->get('/produccion/cronograma');

        // Assert
        $response->assertRedirect('/login');
    }

    public function test_usuarios_without_role_are_forbidden(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->get('/produccion/cronograma');

        // Assert
        $response->assertForbidden();
    }

    public function test_a_supervisor_can_see_the_schedule(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $this->ordenConRuta();

        // Act
        $response = $this->actingAs($usuario)->get('/produccion/cronograma');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Produccion/Cronograma/Index')
            ->has('cronograma.filas', 1)
            ->has('cronograma.dias')
            ->has('motivos'));
    }

    public function test_a_supervisor_can_reorder_an_order(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $orden = $this->ordenConRuta();

        // Act
        $response = $this->actingAs($usuario)->put("/produccion/cronograma/{$orden->id}/prioridad", [
            'prioridad' => 1,
            'motivo' => MotivoCambioPrioridad::UrgenciaComercial->value,
            'detalle' => null,
        ]);

        // Assert
        $response->assertRedirect('/produccion/cronograma');
        $this->assertSame(1, (int) $orden->refresh()->prioridad_actual);
        $this->assertDatabaseHas('registros_de_prioridad', [
            'orden_de_produccion_id' => $orden->id,
            'prioridad_nueva' => 1,
        ]);
    }

    public function test_reordering_an_in_progress_order_returns_an_error(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $orden = $this->ordenConRuta();
        $orden->estado = EstadoOrdenProduccion::EnCurso->value;
        $orden->save();

        // Act
        $response = $this->actingAs($usuario)
            ->from('/produccion/cronograma')
            ->put("/produccion/cronograma/{$orden->id}/prioridad", [
                'prioridad' => 1,
                'motivo' => MotivoCambioPrioridad::Otro->value,
            ]);

        // Assert
        $response->assertRedirect('/produccion/cronograma');
        $response->assertSessionHasErrors('estado');
    }
}
