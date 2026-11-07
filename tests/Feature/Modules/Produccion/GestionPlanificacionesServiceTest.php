<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Models\PlanificacionDeProduccion;
use App\Modules\Produccion\Services\GestionPlanificacionesService;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GestionPlanificacionesServiceTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole(Rol::SupervisorProduccion->value);

        return $usuario;
    }

    public function test_creates_a_planificacion_and_registers_audit(): void
    {
        // Arrange
        $actor = $this->actor();
        $service = app(GestionPlanificacionesService::class);

        // Act
        $planificacion = $service->crear($actor, [
            'nombre' => 'Semana 12',
            'fecha_inicio' => '2026-11-02',
            'fecha_fin' => '2026-11-08',
            'observaciones' => 'Turno completo',
        ], '127.0.0.1');

        // Assert
        $this->assertSame('Semana 12', $planificacion->nombre);
        $this->assertDatabaseHas('planificaciones_de_produccion', ['nombre' => 'Semana 12']);
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'crear_planificacion',
            'usuario_id' => $actor->id,
        ]);
    }

    public function test_lists_and_searches_planificaciones(): void
    {
        // Arrange
        PlanificacionDeProduccion::factory()->create(['nombre' => 'Semana 12']);
        PlanificacionDeProduccion::factory()->create(['nombre' => 'Semana 13']);

        // Act
        $resultados = app(GestionPlanificacionesService::class)->listar(['buscar' => 'Semana 13']);

        // Assert
        $this->assertCount(1, $resultados->items());
        $this->assertSame('Semana 13', $resultados->items()[0]['nombre']);
    }

    public function test_updates_a_planificacion(): void
    {
        // Arrange
        $actor = $this->actor();
        $planificacion = PlanificacionDeProduccion::factory()->create(['nombre' => 'Semana 12']);

        // Act
        $actualizada = app(GestionPlanificacionesService::class)->actualizar($actor, $planificacion, [
            'nombre' => 'Semana 12 ajustada',
            'fecha_inicio' => '2026-11-02',
            'fecha_fin' => '2026-11-09',
            'observaciones' => null,
        ], '127.0.0.1');

        // Assert
        $this->assertSame('Semana 12 ajustada', $actualizada->nombre);
        $this->assertDatabaseHas('eventos_de_auditoria', ['accion' => 'actualizar_planificacion']);
    }

    public function test_rejects_a_period_that_ends_before_it_starts(): void
    {
        // Arrange
        $actor = $this->actor();
        $service = app(GestionPlanificacionesService::class);

        // Act / Assert
        $this->expectException(ValidationException::class);

        $service->crear($actor, [
            'nombre' => 'Semana invertida',
            'fecha_inicio' => '2026-11-08',
            'fecha_fin' => '2026-11-02',
        ]);
    }

    public function test_blocks_deleting_a_planificacion_with_ordenes(): void
    {
        // Arrange
        $actor = $this->actor();
        $planificacion = PlanificacionDeProduccion::factory()->create();
        OrdenDeProduccion::factory()->create(['planificacion_de_produccion_id' => $planificacion->id]);

        // Act / Assert
        $this->expectException(ValidationException::class);

        app(GestionPlanificacionesService::class)->eliminar($actor, $planificacion, '127.0.0.1');
    }

    public function test_deletes_a_planificacion_without_ordenes(): void
    {
        // Arrange
        $actor = $this->actor();
        $planificacion = PlanificacionDeProduccion::factory()->create();

        // Act
        app(GestionPlanificacionesService::class)->eliminar($actor, $planificacion, '127.0.0.1');

        // Assert
        $this->assertDatabaseMissing('planificaciones_de_produccion', ['id' => $planificacion->id]);
        $this->assertDatabaseHas('eventos_de_auditoria', ['accion' => 'eliminar_planificacion']);
    }
}
