<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Produccion\Enums\EstadoMaquinaria;
use App\Modules\Produccion\Models\IntervencionDeMantenimiento;
use App\Modules\Produccion\Models\Maquinaria;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntervencionDeMantenimientoControllerTest extends TestCase
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
        // Arrange
        $maquinaria = Maquinaria::factory()->create();

        // Act
        $response = $this->post("/produccion/maquinarias/{$maquinaria->id}/mantenimientos", [
            'maquinaria_id' => $maquinaria->id,
            'fecha' => '2026-11-01',
            'tipo' => 'Preventivo',
            'responsable' => 'Taller interno',
        ]);

        // Assert
        $response->assertRedirect('/login');
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();
        $maquinaria = Maquinaria::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->post("/produccion/maquinarias/{$maquinaria->id}/mantenimientos", [
            'maquinaria_id' => $maquinaria->id,
            'fecha' => '2026-11-01',
            'tipo' => 'Preventivo',
            'responsable' => 'Taller interno',
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_supervisor_can_register_an_intervention(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $maquinaria = Maquinaria::factory()->create([
            'estado_operativo' => EstadoMaquinaria::Bloqueada->value,
            'desgaste_acumulado' => 1500,
        ]);

        // Act
        $response = $this->actingAs($usuario)
            ->from('/produccion/planes-de-mantenimiento')
            ->post("/produccion/maquinarias/{$maquinaria->id}/mantenimientos", [
                'maquinaria_id' => $maquinaria->id,
                'fecha' => '2026-11-01',
                'tipo' => 'Preventivo',
                'responsable' => 'Taller interno',
                'observaciones' => 'Cambio de rodillos',
            ]);

        // Assert
        $response->assertRedirect('/produccion/planes-de-mantenimiento');
        $response->assertSessionHas('success', 'Intervención registrada.');
        $this->assertDatabaseHas('intervenciones_de_mantenimiento', [
            'maquinaria_id' => $maquinaria->id,
            'fecha' => '2026-11-01',
            'tipo' => 'Preventivo',
            'responsable' => 'Taller interno',
        ]);
        $maquinaria->refresh();
        $this->assertSame(EstadoMaquinaria::Disponible->value, $maquinaria->estado_operativo);
        $this->assertSame(0.0, (float) $maquinaria->desgaste_acumulado);
    }

    public function test_future_dates_are_rejected(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $maquinaria = Maquinaria::factory()->create();

        // Act
        $response = $this->actingAs($usuario)
            ->from('/produccion/planes-de-mantenimiento')
            ->post("/produccion/maquinarias/{$maquinaria->id}/mantenimientos", [
                'maquinaria_id' => $maquinaria->id,
                'fecha' => '2099-01-01',
                'tipo' => 'Preventivo',
                'responsable' => 'Taller interno',
            ]);

        // Assert
        $response->assertRedirect('/produccion/planes-de-mantenimiento');
        $response->assertSessionHasErrors('fecha');
    }

    public function test_only_one_intervention_per_machine_and_day_is_allowed(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $maquinaria = Maquinaria::factory()->create();
        IntervencionDeMantenimiento::factory()->create([
            'maquinaria_id' => $maquinaria->id,
            'fecha' => '2026-11-01',
        ]);

        // Act
        $response = $this->actingAs($usuario)
            ->post("/produccion/maquinarias/{$maquinaria->id}/mantenimientos", [
                'maquinaria_id' => $maquinaria->id,
                'fecha' => '2026-11-01',
                'tipo' => 'Correctivo',
                'responsable' => 'Taller externo',
            ]);

        // Assert
        $response->assertSessionHasErrors('fecha');
        $this->assertDatabaseCount('intervenciones_de_mantenimiento', 1);
    }

    public function test_supervisor_can_update_an_intervention(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $maquinaria = Maquinaria::factory()->create();
        $intervencion = IntervencionDeMantenimiento::factory()->create([
            'maquinaria_id' => $maquinaria->id,
            'fecha' => '2026-11-01',
            'tipo' => 'Preventivo',
            'responsable' => 'Taller interno',
        ]);

        // Act
        $response = $this->actingAs($usuario)->put(
            "/produccion/maquinarias/{$maquinaria->id}/mantenimientos/{$intervencion->id}",
            [
                'fecha' => '2026-11-02',
                'tipo' => 'Correctivo',
                'responsable' => 'Taller externo',
                'observaciones' => 'Se cambió el rodamiento.',
            ],
        );

        // Assert
        $response->assertRedirect('/produccion/planes-de-mantenimiento');
        $this->assertDatabaseHas('intervenciones_de_mantenimiento', [
            'id' => $intervencion->id,
            'fecha' => '2026-11-02',
            'tipo' => 'Correctivo',
            'responsable' => 'Taller externo',
        ]);
    }

    public function test_supervisor_can_delete_an_intervention(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $maquinaria = Maquinaria::factory()->create();
        $intervencion = IntervencionDeMantenimiento::factory()->create([
            'maquinaria_id' => $maquinaria->id,
        ]);

        // Act
        $response = $this->actingAs($usuario)->delete(
            "/produccion/maquinarias/{$maquinaria->id}/mantenimientos/{$intervencion->id}",
        );

        // Assert
        $response->assertRedirect('/produccion/planes-de-mantenimiento');
        $this->assertDatabaseMissing('intervenciones_de_mantenimiento', ['id' => $intervencion->id]);
    }

    public function test_an_intervention_from_another_machine_cannot_be_updated(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $maquinaria = Maquinaria::factory()->create();
        $otraMaquinaria = Maquinaria::factory()->create();
        $intervencion = IntervencionDeMantenimiento::factory()->create([
            'maquinaria_id' => $otraMaquinaria->id,
            'fecha' => '2026-11-01',
        ]);

        // Act
        $response = $this->actingAs($usuario)->put(
            "/produccion/maquinarias/{$maquinaria->id}/mantenimientos/{$intervencion->id}",
            [
                'fecha' => '2026-11-02',
                'tipo' => 'Correctivo',
                'responsable' => 'Taller externo',
            ],
        );

        // Assert
        $response->assertSessionHasErrors('intervencion');
    }
}
