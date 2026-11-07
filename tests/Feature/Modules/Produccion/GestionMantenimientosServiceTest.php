<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Produccion\Enums\EstadoMaquinaria;
use App\Modules\Produccion\Models\Maquinaria;
use App\Modules\Produccion\Services\GestionMantenimientosService;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Factories\IntervencionDeMantenimientoFactory;
use Database\Factories\PlanDeMantenimientoFactory;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GestionMantenimientosServiceTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole(Rol::SupervisorProduccion->value);

        return $usuario;
    }

    private function service(): GestionMantenimientosService
    {
        return app(GestionMantenimientosService::class);
    }

    public function test_registers_intervention_and_resets_wear_and_unblocks_machine(): void
    {
        // Arrange
        $actor = $this->actor();
        $maquinaria = Maquinaria::factory()->create([
            'estado_operativo' => EstadoMaquinaria::Bloqueada->value,
            'desgaste_acumulado' => 1500,
        ]);
        PlanDeMantenimientoFactory::new()->create([
            'maquinaria_id' => $maquinaria->id,
            'umbral_uso' => 1000,
        ]);

        // Act
        $intervencion = $this->service()->registrar(
            $actor,
            $maquinaria,
            '2026-11-01',
            'Preventivo',
            'Taller interno',
            'Cambio de rodillos',
            '127.0.0.1',
        );

        // Assert
        $maquinaria->refresh();
        $this->assertSame($maquinaria->id, $intervencion->maquinaria_id);
        $this->assertSame('2026-11-01', $intervencion->fecha->toDateString());
        $this->assertSame('Preventivo', $intervencion->tipo);
        $this->assertSame('Taller interno', $intervencion->responsable);
        $this->assertSame('Cambio de rodillos', $intervencion->observaciones);
        $this->assertSame(EstadoMaquinaria::Disponible->value, $maquinaria->estado_operativo);
        $this->assertSame(0.0, (float) $maquinaria->desgaste_acumulado);
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'registrar_intervencion_mantenimiento',
            'usuario_id' => $actor->id,
        ]);
    }

    public function test_registers_intervention_on_available_machine(): void
    {
        // Arrange
        $actor = $this->actor();
        $maquinaria = Maquinaria::factory()->create([
            'estado_operativo' => EstadoMaquinaria::Disponible->value,
            'desgaste_acumulado' => 250,
        ]);

        // Act
        $this->service()->registrar(
            $actor,
            $maquinaria,
            '2026-11-01',
            'Correctivo',
            'Mecánico A',
            null,
            '127.0.0.1',
        );

        // Assert
        $maquinaria->refresh();
        $this->assertSame(EstadoMaquinaria::Disponible->value, $maquinaria->estado_operativo);
        $this->assertSame(0.0, (float) $maquinaria->desgaste_acumulado);
    }

    public function test_evaluar_bloqueo_blocks_machine_when_wear_reaches_threshold(): void
    {
        // Arrange
        $actor = $this->actor();
        $maquinaria = Maquinaria::factory()->create([
            'estado_operativo' => EstadoMaquinaria::Disponible->value,
            'desgaste_acumulado' => 1000,
        ]);
        PlanDeMantenimientoFactory::new()->create([
            'maquinaria_id' => $maquinaria->id,
            'umbral_uso' => 1000,
        ]);

        // Act
        $bloqueo = $this->service()->evaluarBloqueo($actor, $maquinaria, '127.0.0.1');

        // Assert
        $this->assertTrue($bloqueo);
        $this->assertSame(EstadoMaquinaria::Bloqueada->value, $maquinaria->refresh()->estado_operativo);
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'bloquear_maquinaria_por_uso',
            'usuario_id' => $actor->id,
        ]);
    }

    public function test_evaluar_bloqueo_does_not_block_below_threshold(): void
    {
        // Arrange
        $actor = $this->actor();
        $maquinaria = Maquinaria::factory()->create([
            'estado_operativo' => EstadoMaquinaria::Disponible->value,
            'desgaste_acumulado' => 999.99,
        ]);
        PlanDeMantenimientoFactory::new()->create([
            'maquinaria_id' => $maquinaria->id,
            'umbral_uso' => 1000,
        ]);

        // Act
        $bloqueo = $this->service()->evaluarBloqueo($actor, $maquinaria);

        // Assert
        $this->assertFalse($bloqueo);
        $this->assertSame(EstadoMaquinaria::Disponible->value, $maquinaria->refresh()->estado_operativo);
    }

    public function test_evaluar_bloqueo_does_not_block_without_plan(): void
    {
        // Arrange
        $actor = $this->actor();
        $maquinaria = Maquinaria::factory()->create([
            'estado_operativo' => EstadoMaquinaria::Disponible->value,
            'desgaste_acumulado' => 5000,
        ]);

        // Act
        $bloqueo = $this->service()->evaluarBloqueo($actor, $maquinaria);

        // Assert
        $this->assertFalse($bloqueo);
        $this->assertSame(EstadoMaquinaria::Disponible->value, $maquinaria->refresh()->estado_operativo);
    }

    public function test_evaluar_bloqueo_does_not_block_non_available_states(): void
    {
        // Arrange
        $actor = $this->actor();
        $maquinaria = Maquinaria::factory()->create([
            'estado_operativo' => EstadoMaquinaria::EnMantenimiento->value,
            'desgaste_acumulado' => 2000,
        ]);
        PlanDeMantenimientoFactory::new()->create([
            'maquinaria_id' => $maquinaria->id,
            'umbral_uso' => 1000,
        ]);

        // Act
        $bloqueo = $this->service()->evaluarBloqueo($actor, $maquinaria);

        // Assert
        $this->assertFalse($bloqueo);
        $this->assertSame(EstadoMaquinaria::EnMantenimiento->value, $maquinaria->refresh()->estado_operativo);
    }

    public function test_lists_interventions_ordered_by_date_desc(): void
    {
        // Arrange
        $this->actor();
        $maquinaria = Maquinaria::factory()->create();
        IntervencionDeMantenimientoFactory::new()->create([
            'maquinaria_id' => $maquinaria->id,
            'fecha' => '2026-10-01',
        ]);
        IntervencionDeMantenimientoFactory::new()->create([
            'maquinaria_id' => $maquinaria->id,
            'fecha' => '2026-11-01',
        ]);
        $otra = Maquinaria::factory()->create();
        IntervencionDeMantenimientoFactory::new()->create([
            'maquinaria_id' => $otra->id,
            'fecha' => '2026-12-01',
        ]);

        // Act
        $todas = $this->service()->intervenciones();
        $porMaquina = $this->service()->intervenciones($maquinaria->id);

        // Assert
        $this->assertCount(3, $todas);
        $this->assertSame('2026-12-01', $todas->first()['fecha']);
        $this->assertCount(2, $porMaquina);
        $this->assertSame('2026-11-01', $porMaquina->first()['fecha']);
    }
}
