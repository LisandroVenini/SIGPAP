<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Produccion\Enums\EstadoMaquinaria;
use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use App\Modules\Produccion\Models\Maquinaria;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Services\GestionMaquinariasService;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Factories\IntervencionDeMantenimientoFactory;
use Database\Factories\PlanDeMantenimientoFactory;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GestionMaquinariasServiceTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole(Rol::SupervisorProduccion->value);

        return $usuario;
    }

    public function test_creates_a_machine_with_fixed_state_and_audit(): void
    {
        // Arrange
        $actor = $this->actor();
        $service = app(GestionMaquinariasService::class);

        // Act
        $maquinaria = $service->crear($actor, ['nombre' => 'Lijadora orbital', 'tipo' => 'Lijado'], '127.0.0.1');

        // Assert
        $this->assertSame('Lijadora orbital', $maquinaria->nombre);
        $this->assertSame('Lijado', $maquinaria->tipo);
        $this->assertSame(EstadoMaquinaria::Disponible->value, $maquinaria->estado_operativo);
        $this->assertSame(0.0, (float) $maquinaria->desgaste_acumulado);
        $this->assertDatabaseHas('maquinarias', [
            'nombre' => 'Lijadora orbital',
            'estado_operativo' => EstadoMaquinaria::Disponible->value,
            'desgaste_acumulado' => 0,
        ]);
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'crear_maquinaria',
            'usuario_id' => $actor->id,
        ]);
    }

    public function test_lists_and_searches_machines(): void
    {
        // Arrange
        Maquinaria::factory()->create(['nombre' => 'Lijadora orbital', 'tipo' => 'Lijado']);
        Maquinaria::factory()->create(['nombre' => 'Pistola de pintura', 'tipo' => 'Pintado']);

        // Act
        $resultados = app(GestionMaquinariasService::class)->listar(['buscar' => 'pint']);

        // Assert
        $this->assertCount(1, $resultados->items());
        $this->assertSame('Pistola de pintura', $resultados->items()[0]['nombre']);
    }

    public function test_search_escapes_wildcard_characters(): void
    {
        // Arrange
        Maquinaria::factory()->create(['nombre' => 'Sierra 100% industrial', 'tipo' => 'Corte']);
        Maquinaria::factory()->create(['nombre' => 'Otra máquina', 'tipo' => 'Corte']);

        // Act
        $resultados = app(GestionMaquinariasService::class)->listar(['buscar' => '100%']);

        // Assert
        $this->assertCount(1, $resultados->items());
        $this->assertSame('Sierra 100% industrial', $resultados->items()[0]['nombre']);
    }

    public function test_updates_only_name_and_type(): void
    {
        // Arrange
        $actor = $this->actor();
        $maquinaria = Maquinaria::factory()->create([
            'nombre' => 'Lijadora',
            'tipo' => 'Lijado',
            'estado_operativo' => EstadoMaquinaria::EnMantenimiento->value,
            'desgaste_acumulado' => 25.5,
        ]);

        // Act
        $actualizada = app(GestionMaquinariasService::class)->actualizar(
            $actor,
            $maquinaria,
            ['nombre' => 'Lijadora renovada', 'tipo' => 'Acabado'],
            '127.0.0.1',
        );

        // Assert
        $this->assertSame('Lijadora renovada', $actualizada->nombre);
        $this->assertSame('Acabado', $actualizada->tipo);
        $this->assertSame(EstadoMaquinaria::EnMantenimiento->value, $actualizada->estado_operativo);
        $this->assertSame(25.5, (float) $actualizada->desgaste_acumulado);
        $this->assertDatabaseHas('eventos_de_auditoria', ['accion' => 'actualizar_maquinaria']);
    }

    public function test_blocks_deleting_a_machine_assigned_to_an_in_progress_order(): void
    {
        // Arrange
        $actor = $this->actor();
        $maquinaria = Maquinaria::factory()->create();
        $orden = OrdenDeProduccion::factory()->create(['estado' => EstadoOrdenProduccion::EnCurso->value]);
        $orden->maquinarias()->attach($maquinaria->id);

        // Act / Assert
        $this->expectException(ValidationException::class);

        app(GestionMaquinariasService::class)->eliminar($actor, $maquinaria, '127.0.0.1');
    }

    public function test_blocks_deleting_a_machine_with_maintenance_plans(): void
    {
        // Arrange
        $actor = $this->actor();
        $maquinaria = Maquinaria::factory()->create();
        PlanDeMantenimientoFactory::new()->create(['maquinaria_id' => $maquinaria->id]);

        // Act / Assert
        $this->expectException(ValidationException::class);

        app(GestionMaquinariasService::class)->eliminar($actor, $maquinaria, '127.0.0.1');
    }

    public function test_blocks_deleting_a_machine_with_interventions(): void
    {
        // Arrange
        $actor = $this->actor();
        $maquinaria = Maquinaria::factory()->create();
        IntervencionDeMantenimientoFactory::new()->create(['maquinaria_id' => $maquinaria->id]);

        // Act / Assert
        $this->expectException(ValidationException::class);

        app(GestionMaquinariasService::class)->eliminar($actor, $maquinaria, '127.0.0.1');
    }

    public function test_deletes_an_unrestricted_machine(): void
    {
        // Arrange
        $actor = $this->actor();
        $maquinaria = Maquinaria::factory()->create();

        // Act
        app(GestionMaquinariasService::class)->eliminar($actor, $maquinaria, '127.0.0.1');

        // Assert
        $this->assertDatabaseMissing('maquinarias', ['id' => $maquinaria->id]);
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'eliminar_maquinaria',
            'usuario_id' => $actor->id,
        ]);
    }
}
