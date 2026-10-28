<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use App\Modules\Produccion\Models\Operario;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Services\GestionOperariosService;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GestionOperariosServiceTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole(Rol::SupervisorProduccion->value);

        return $usuario;
    }

    public function test_creates_an_operario_and_registers_audit(): void
    {
        // Arrange
        $actor = $this->actor();

        // Act
        $operario = app(GestionOperariosService::class)->crear($actor, [
            'nombre' => 'Ana',
            'apellido' => 'Pérez',
            'especialidad' => 'Pintura',
        ], '127.0.0.1');

        // Assert
        $this->assertSame('Ana', $operario->nombre);
        $this->assertDatabaseHas('operarios', ['nombre' => 'Ana', 'apellido' => 'Pérez']);
        $this->assertDatabaseHas('eventos_de_auditoria', ['accion' => 'crear_operario', 'usuario_id' => $actor->id]);
    }

    public function test_lists_and_searches_operarios(): void
    {
        // Arrange
        Operario::factory()->create(['nombre' => 'Ana', 'apellido' => 'Pérez', 'especialidad' => 'Pintura']);
        Operario::factory()->create(['nombre' => 'Luis', 'apellido' => 'Gómez', 'especialidad' => 'Carpintería']);

        // Act
        $resultados = app(GestionOperariosService::class)->listar(['buscar' => 'Pintura']);

        // Assert
        $this->assertCount(1, $resultados->items());
        $this->assertSame('Ana', $resultados->items()[0]['nombre']);
    }

    public function test_updates_an_operario(): void
    {
        // Arrange
        $actor = $this->actor();
        $operario = Operario::factory()->create(['especialidad' => 'Pintura']);

        // Act
        $actualizado = app(GestionOperariosService::class)->actualizar($actor, $operario, [
            'nombre' => $operario->nombre,
            'apellido' => $operario->apellido,
            'especialidad' => 'Lijado',
        ], '127.0.0.1');

        // Assert
        $this->assertSame('Lijado', $actualizado->especialidad);
        $this->assertDatabaseHas('eventos_de_auditoria', ['accion' => 'actualizar_operario']);
    }

    public function test_blocks_baja_when_the_operario_is_assigned_to_an_order_in_progress(): void
    {
        // Arrange
        $actor = $this->actor();
        $operario = Operario::factory()->create();
        $orden = OrdenDeProduccion::factory()->create(['estado' => EstadoOrdenProduccion::EnCurso->value]);
        $operario->ordenesDeProduccion()->attach($orden);

        // Act / Assert
        $this->expectException(ValidationException::class);

        app(GestionOperariosService::class)->eliminar($actor, $operario, '127.0.0.1');
    }

    public function test_deletes_an_unassigned_operario(): void
    {
        // Arrange
        $actor = $this->actor();
        $operario = Operario::factory()->create();

        // Act
        app(GestionOperariosService::class)->eliminar($actor, $operario, '127.0.0.1');

        // Assert
        $this->assertSoftDeleted('operarios', ['id' => $operario->id]);
    }
}
