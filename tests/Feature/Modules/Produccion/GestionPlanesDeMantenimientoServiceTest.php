<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Produccion\Enums\EstadoMaquinaria;
use App\Modules\Produccion\Models\Maquinaria;
use App\Modules\Produccion\Models\PlanDeMantenimiento;
use App\Modules\Produccion\Services\GestionPlanesDeMantenimientoService;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Factories\PlanDeMantenimientoFactory;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GestionPlanesDeMantenimientoServiceTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole(Rol::SupervisorProduccion->value);

        return $usuario;
    }

    private function service(): GestionPlanesDeMantenimientoService
    {
        return app(GestionPlanesDeMantenimientoService::class);
    }

    public function test_creates_a_maintenance_plan(): void
    {
        // Arrange
        $actor = $this->actor();
        $maquinaria = Maquinaria::factory()->create();

        // Act
        $plan = $this->service()->crear($actor, [
            'maquinaria_id' => $maquinaria->id,
            'umbral_uso' => 1500,
            'descripcion' => 'Lubricación general',
        ], '127.0.0.1');

        // Assert
        $this->assertInstanceOf(PlanDeMantenimiento::class, $plan);
        $this->assertSame($maquinaria->id, $plan->maquinaria_id);
        $this->assertSame(1500.0, (float) $plan->umbral_uso);
        $this->assertSame('Lubricación general', $plan->descripcion);
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'crear_plan_mantenimiento',
            'usuario_id' => $actor->id,
        ]);
    }

    public function test_rejects_non_positive_threshold_on_create(): void
    {
        // Arrange
        $actor = $this->actor();
        $maquinaria = Maquinaria::factory()->create();

        // Act / Assert
        $this->expectException(ValidationException::class);

        $this->service()->crear($actor, [
            'maquinaria_id' => $maquinaria->id,
            'umbral_uso' => 0,
            'descripcion' => 'Plan inválido',
        ]);
    }

    public function test_rejects_second_plan_for_same_machine(): void
    {
        // Arrange
        $actor = $this->actor();
        $maquinaria = Maquinaria::factory()->create();
        PlanDeMantenimientoFactory::new()->create(['maquinaria_id' => $maquinaria->id]);

        // Act / Assert
        try {
            $this->service()->crear($actor, [
                'maquinaria_id' => $maquinaria->id,
                'umbral_uso' => 500,
                'descripcion' => 'Segundo plan',
            ]);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('maquinaria_id', $e->errors());
        }
    }

    public function test_updates_a_maintenance_plan(): void
    {
        // Arrange
        $actor = $this->actor();
        $maquinaria = Maquinaria::factory()->create();
        $plan = PlanDeMantenimientoFactory::new()->create([
            'maquinaria_id' => $maquinaria->id,
            'umbral_uso' => 1000,
            'descripcion' => 'Cambio de rodillos',
        ]);

        // Act
        $actualizado = $this->service()->actualizar($actor, $plan, [
            'maquinaria_id' => $maquinaria->id,
            'umbral_uso' => 2000,
            'descripcion' => 'Cambio de rodillos y bandas',
        ], '127.0.0.1');

        // Assert
        $this->assertSame(2000.0, (float) $actualizado->umbral_uso);
        $this->assertSame('Cambio de rodillos y bandas', $actualizado->descripcion);
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'actualizar_plan_mantenimiento',
            'usuario_id' => $actor->id,
        ]);
    }

    public function test_rejects_update_to_a_machine_with_another_plan(): void
    {
        // Arrange
        $actor = $this->actor();
        $maquinariaA = Maquinaria::factory()->create();
        $maquinariaB = Maquinaria::factory()->create();
        $planA = PlanDeMantenimientoFactory::new()->create(['maquinaria_id' => $maquinariaA->id]);
        PlanDeMantenimientoFactory::new()->create(['maquinaria_id' => $maquinariaB->id]);

        // Act / Assert
        $this->expectException(ValidationException::class);

        $this->service()->actualizar($actor, $planA, [
            'maquinaria_id' => $maquinariaB->id,
            'umbral_uso' => 500,
            'descripcion' => 'Robar plan',
        ]);
    }

    public function test_lists_and_searches_plans_ordered_by_machine_name(): void
    {
        // Arrange
        $actor = $this->actor();
        $lijadora = Maquinaria::factory()->create(['nombre' => 'Lijadora orbital']);
        $pistola = Maquinaria::factory()->create(['nombre' => 'Pistola de pintura']);
        PlanDeMantenimientoFactory::new()->create([
            'maquinaria_id' => $lijadora->id,
            'descripcion' => 'Lijado fino',
        ]);
        PlanDeMantenimientoFactory::new()->create([
            'maquinaria_id' => $pistola->id,
            'descripcion' => 'Limpieza de boquilla',
        ]);

        // Act
        $resultados = $this->service()->listar(['buscar' => 'pint']);

        // Assert
        $this->assertCount(1, $resultados->items());
        $this->assertSame('Pistola de pintura', $resultados->items()[0]['maquinaria']);

        $todos = $this->service()->listar([]);
        $this->assertSame('Lijadora orbital', $todos->items()[0]['maquinaria']);
        $this->assertSame('Pistola de pintura', $todos->items()[1]['maquinaria']);
    }

    public function test_lists_blocked_machine_flag(): void
    {
        // Arrange
        $actor = $this->actor();
        $maquinaria = Maquinaria::factory()->create([
            'nombre' => 'Sierra',
            'estado_operativo' => EstadoMaquinaria::Bloqueada->value,
        ]);
        PlanDeMantenimientoFactory::new()->create(['maquinaria_id' => $maquinaria->id]);

        // Act
        $resultados = $this->service()->listar([]);

        // Assert
        $this->assertTrue($resultados->items()[0]['maquina_bloqueada']);
    }

    public function test_blocks_deleting_plan_for_blocked_machine(): void
    {
        // Arrange
        $actor = $this->actor();
        $maquinaria = Maquinaria::factory()->create([
            'estado_operativo' => EstadoMaquinaria::Bloqueada->value,
        ]);
        $plan = PlanDeMantenimientoFactory::new()->create(['maquinaria_id' => $maquinaria->id]);

        // Act / Assert
        try {
            $this->service()->eliminar($actor, $plan, '127.0.0.1');
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('plan', $e->errors());
            $this->assertStringContainsString('mantenimiento pendiente', $e->errors()['plan'][0]);
        }
    }

    public function test_deletes_plan_for_available_machine(): void
    {
        // Arrange
        $actor = $this->actor();
        $maquinaria = Maquinaria::factory()->create([
            'estado_operativo' => EstadoMaquinaria::Disponible->value,
        ]);
        $plan = PlanDeMantenimientoFactory::new()->create(['maquinaria_id' => $maquinaria->id]);

        // Act
        $this->service()->eliminar($actor, $plan, '127.0.0.1');

        // Assert
        $this->assertDatabaseMissing('planes_de_mantenimiento', ['id' => $plan->id]);
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'eliminar_plan_mantenimiento',
            'usuario_id' => $actor->id,
        ]);
    }

    public function test_management_permission_check_returns_true_for_supervisor(): void
    {
        // Arrange
        $actor = $this->actor();

        // Act / Assert
        $this->assertTrue($this->service()->puedeGestionar($actor));
    }
}
