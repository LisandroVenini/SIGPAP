<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Produccion\Models\AsignacionDeEtapa;
use App\Modules\Produccion\Models\Etapa;
use App\Modules\Produccion\Services\GestionEtapasService;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GestionEtapasServiceTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole(Rol::SupervisorProduccion->value);

        return $usuario;
    }

    public function test_creates_an_etapa_and_registers_audit(): void
    {
        // Arrange
        $actor = $this->actor();
        $service = app(GestionEtapasService::class);

        // Act
        $etapa = $service->crear($actor, ['nombre' => 'Lijado', 'descripcion' => 'Lijado de madera'], '127.0.0.1');

        // Assert
        $this->assertSame('Lijado', $etapa->nombre);
        $this->assertDatabaseHas('etapas', ['nombre' => 'Lijado']);
        $this->assertDatabaseHas('eventos_de_auditoria', ['accion' => 'crear_etapa', 'usuario_id' => $actor->id]);
    }

    public function test_lists_and_searches_etapas(): void
    {
        // Arrange
        Etapa::factory()->create(['nombre' => 'Lijado']);
        Etapa::factory()->create(['nombre' => 'Pintado']);

        // Act
        $resultados = app(GestionEtapasService::class)->listar(['buscar' => 'Pint']);

        // Assert
        $this->assertCount(1, $resultados->items());
        $this->assertSame('Pintado', $resultados->items()[0]['nombre']);
    }

    public function test_updates_an_etapa(): void
    {
        // Arrange
        $actor = $this->actor();
        $etapa = Etapa::factory()->create(['nombre' => 'Lijado']);

        // Act
        $actualizada = app(GestionEtapasService::class)->actualizar(
            $actor,
            $etapa,
            ['nombre' => 'Lijado fino', 'descripcion' => null],
            '127.0.0.1',
        );

        // Assert
        $this->assertSame('Lijado fino', $actualizada->nombre);
        $this->assertDatabaseHas('eventos_de_auditoria', ['accion' => 'actualizar_etapa']);
    }

    public function test_blocks_deleting_an_etapa_assigned_to_a_product(): void
    {
        // Arrange
        $actor = $this->actor();
        $etapa = Etapa::factory()->create();
        AsignacionDeEtapa::factory()->create(['etapa_id' => $etapa->id]);

        // Act / Assert
        $this->expectException(ValidationException::class);

        app(GestionEtapasService::class)->eliminar($actor, $etapa, '127.0.0.1');
    }

    public function test_deletes_an_unassigned_etapa(): void
    {
        // Arrange
        $actor = $this->actor();
        $etapa = Etapa::factory()->create();

        // Act
        app(GestionEtapasService::class)->eliminar($actor, $etapa, '127.0.0.1');

        // Assert
        $this->assertSoftDeleted('etapas', ['id' => $etapa->id]);
    }
}
