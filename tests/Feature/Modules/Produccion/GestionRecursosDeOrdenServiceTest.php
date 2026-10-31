<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Inventario\Enums\EstadoArticulo;
use App\Modules\Inventario\Enums\EstadoReserva;
use App\Modules\Inventario\Enums\OrigenArticulo;
use App\Modules\Inventario\Enums\TipoArticulo;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Models\Reserva;
use App\Modules\Produccion\Enums\EstadoMaquinaria;
use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use App\Modules\Produccion\Models\ComponenteDeReceta;
use App\Modules\Produccion\Models\Maquinaria;
use App\Modules\Produccion\Models\Operario;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Models\Receta;
use App\Modules\Produccion\Services\GestionRecursosDeOrdenService;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GestionRecursosDeOrdenServiceTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole(Rol::SupervisorProduccion->value);

        return $usuario;
    }

    private function service(): GestionRecursosDeOrdenService
    {
        return app(GestionRecursosDeOrdenService::class);
    }

    private function productoFabricado(): Articulo
    {
        return Articulo::factory()->create([
            'tipo' => TipoArticulo::ProductoTerminado->value,
            'origen' => OrigenArticulo::Fabricado->value,
            'estado' => EstadoArticulo::Activo->value,
        ]);
    }

    private function insumo(float $stock): Articulo
    {
        return Articulo::factory()->create([
            'tipo' => TipoArticulo::MateriaPrima->value,
            'estado' => EstadoArticulo::Activo->value,
            'stock_fisico' => $stock,
        ]);
    }

    private function recetaConComponente(Articulo $producto, Articulo $insumo, float $cantidadPorUnidad): void
    {
        $receta = Receta::factory()->create(['articulo_id' => $producto->id]);
        ComponenteDeReceta::factory()->create([
            'receta_id' => $receta->id,
            'articulo_id' => $insumo->id,
            'cantidad' => $cantidadPorUnidad,
        ]);
    }

    public function test_assigning_resources_changes_state_to_asignada_and_syncs_pivots(): void
    {
        // Arrange
        $actor = $this->actor();
        $orden = OrdenDeProduccion::factory()->create([
            'estado' => EstadoOrdenProduccion::Pendiente->value,
        ]);
        $operario = Operario::factory()->create(['apellido' => 'García', 'nombre' => 'Ana']);
        $maquinaria = Maquinaria::factory()->create([
            'nombre' => 'Lijadora orbital',
            'estado_operativo' => EstadoMaquinaria::Disponible->value,
        ]);

        // Act
        $this->service()->asignar($actor, $orden, [$operario->id], [$maquinaria->id], '127.0.0.1');

        // Assert
        $orden->refresh();
        $this->assertSame(EstadoOrdenProduccion::Asignada->value, $orden->estado);
        $this->assertTrue($orden->operarios->contains($operario));
        $this->assertTrue($orden->maquinarias->contains($maquinaria));
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'asignar_recursos_orden',
            'usuario_id' => $actor->id,
        ]);
    }

    public function test_reassigning_updates_pivots(): void
    {
        // Arrange
        $actor = $this->actor();
        $orden = OrdenDeProduccion::factory()->create([
            'estado' => EstadoOrdenProduccion::Asignada->value,
        ]);
        $operarioA = Operario::factory()->create();
        $operarioB = Operario::factory()->create();
        $maquinariaA = Maquinaria::factory()->create();
        $maquinariaB = Maquinaria::factory()->create();
        $orden->operarios()->attach($operarioA);
        $orden->maquinarias()->attach($maquinariaA);

        // Act
        $this->service()->asignar($actor, $orden, [$operarioB->id], [$maquinariaB->id], '127.0.0.1');

        // Assert
        $orden->refresh();
        $this->assertFalse($orden->operarios->contains($operarioA));
        $this->assertTrue($orden->operarios->contains($operarioB));
        $this->assertFalse($orden->maquinarias->contains($maquinariaA));
        $this->assertTrue($orden->maquinarias->contains($maquinariaB));
    }

    public function test_unassigning_all_reverts_to_pendiente_when_reserves_are_covered(): void
    {
        // Arrange
        $actor = $this->actor();
        $insumo = $this->insumo(100);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 10);
        $orden = OrdenDeProduccion::factory()->create([
            'articulo_id' => $producto->id,
            'estado' => EstadoOrdenProduccion::Asignada->value,
            'cantidad_requerida' => 5,
        ]);
        Reserva::factory()->create([
            'articulo_id' => $insumo->id,
            'orden_de_produccion_id' => $orden->id,
            'cantidad' => 50,
            'estado' => EstadoReserva::Vigente->value,
        ]);
        $operario = Operario::factory()->create();
        $maquinaria = Maquinaria::factory()->create();
        $orden->operarios()->attach($operario);
        $orden->maquinarias()->attach($maquinaria);

        // Act
        $this->service()->asignar($actor, $orden, [], [], '127.0.0.1');

        // Assert
        $orden->refresh();
        $this->assertSame(EstadoOrdenProduccion::Pendiente->value, $orden->estado);
        $this->assertCount(0, $orden->operarios);
        $this->assertCount(0, $orden->maquinarias);
    }

    public function test_unassigning_all_reverts_to_pendiente_por_insumos_when_reserves_are_not_covered(): void
    {
        // Arrange
        $actor = $this->actor();
        $insumo = $this->insumo(100);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 10);
        $orden = OrdenDeProduccion::factory()->create([
            'articulo_id' => $producto->id,
            'estado' => EstadoOrdenProduccion::Asignada->value,
            'cantidad_requerida' => 5,
        ]);
        Reserva::factory()->create([
            'articulo_id' => $insumo->id,
            'orden_de_produccion_id' => $orden->id,
            'cantidad' => 40,
            'estado' => EstadoReserva::Vigente->value,
        ]);
        $operario = Operario::factory()->create();
        $maquinaria = Maquinaria::factory()->create();
        $orden->operarios()->attach($operario);
        $orden->maquinarias()->attach($maquinaria);

        // Act
        $this->service()->asignar($actor, $orden, [], [], '127.0.0.1');

        // Assert
        $orden->refresh();
        $this->assertSame(EstadoOrdenProduccion::PendientePorInsumos->value, $orden->estado);
    }

    public function test_rejects_unavailable_machinery(): void
    {
        // Arrange
        $actor = $this->actor();
        $orden = OrdenDeProduccion::factory()->create([
            'estado' => EstadoOrdenProduccion::Pendiente->value,
        ]);
        $operario = Operario::factory()->create();
        $maquinaria = Maquinaria::factory()->create([
            'estado_operativo' => EstadoMaquinaria::EnMantenimiento->value,
        ]);

        // Act / Assert
        $this->expectException(ValidationException::class);

        $this->service()->asignar($actor, $orden, [$operario->id], [$maquinaria->id], '127.0.0.1');
    }

    public function test_blocks_assignment_when_order_is_en_curso(): void
    {
        // Arrange
        $actor = $this->actor();
        $orden = OrdenDeProduccion::factory()->create([
            'estado' => EstadoOrdenProduccion::EnCurso->value,
        ]);
        $operario = Operario::factory()->create();
        $maquinaria = Maquinaria::factory()->create();

        // Act / Assert
        $this->expectException(ValidationException::class);

        $this->service()->asignar($actor, $orden, [$operario->id], [$maquinaria->id], '127.0.0.1');
    }

    public function test_starting_without_resources_fails(): void
    {
        // Arrange
        $actor = $this->actor();
        $orden = OrdenDeProduccion::factory()->create([
            'estado' => EstadoOrdenProduccion::Asignada->value,
        ]);

        // Act / Assert
        $this->expectException(ValidationException::class);

        $this->service()->iniciar($actor, $orden, '127.0.0.1');
    }

    public function test_starting_changes_state_to_en_curso_and_sets_start_date(): void
    {
        // Arrange
        $actor = $this->actor();
        $orden = OrdenDeProduccion::factory()->create([
            'estado' => EstadoOrdenProduccion::Asignada->value,
        ]);
        $operario = Operario::factory()->create();
        $maquinaria = Maquinaria::factory()->create();
        $orden->operarios()->attach($operario);
        $orden->maquinarias()->attach($maquinaria);

        // Act
        $this->service()->iniciar($actor, $orden, '127.0.0.1');

        // Assert
        $orden->refresh();
        $this->assertSame(EstadoOrdenProduccion::EnCurso->value, $orden->estado);
        $this->assertNotNull($orden->fecha_inicio);
        $this->assertSame(now()->toDateString(), $orden->fecha_inicio->toDateString());
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'iniciar_orden_produccion',
            'usuario_id' => $actor->id,
        ]);
    }

    public function test_starting_is_blocked_when_order_is_not_asignada(): void
    {
        // Arrange
        $actor = $this->actor();
        $orden = OrdenDeProduccion::factory()->create([
            'estado' => EstadoOrdenProduccion::Pendiente->value,
        ]);
        $operario = Operario::factory()->create();
        $maquinaria = Maquinaria::factory()->create();
        $orden->operarios()->attach($operario);
        $orden->maquinarias()->attach($maquinaria);

        // Act / Assert
        $this->expectException(ValidationException::class);

        $this->service()->iniciar($actor, $orden, '127.0.0.1');
    }
}
