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
use App\Modules\Produccion\Models\ParteDiario;
use App\Modules\Produccion\Models\PlanificacionDeProduccion;
use App\Modules\Produccion\Services\GestionCronogramaService;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GestionCronogramaServiceTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole(Rol::SupervisorProduccion->value);

        return $usuario;
    }

    private function planificacion(): PlanificacionDeProduccion
    {
        return PlanificacionDeProduccion::factory()->create();
    }

    private function producto(): Articulo
    {
        return Articulo::factory()->create([
            'tipo' => TipoArticulo::ProductoTerminado->value,
            'origen' => OrigenArticulo::Fabricado->value,
            'estado' => EstadoArticulo::Activo->value,
        ]);
    }

    private function ruta(Articulo $producto, array $etapas): void
    {
        foreach ($etapas as $orden => $tiempo) {
            $etapa = Etapa::factory()->create();

            AsignacionDeEtapa::factory()->create([
                'articulo_id' => $producto->id,
                'etapa_id' => $etapa->id,
                'tiempo_por_unidad' => $tiempo,
                'orden' => $orden + 1,
            ]);
        }
    }

    private function orden(array $atributos): OrdenDeProduccion
    {
        return OrdenDeProduccion::factory()->create($atributos);
    }

    public function test_projects_orders_by_priority_and_computes_stage_durations(): void
    {
        // Arrange
        $planificacion = $this->planificacion();
        $producto = $this->producto();
        $this->ruta($producto, [60, 30]);

        $primera = $this->orden([
            'articulo_id' => $producto->id,
            'planificacion_de_produccion_id' => $planificacion->id,
            'prioridad_actual' => 1,
            'cantidad_requerida' => 8,
            'fecha_entrega' => now()->addDays(30)->toDateString(),
        ]);
        $segunda = $this->orden([
            'articulo_id' => $producto->id,
            'planificacion_de_produccion_id' => $planificacion->id,
            'prioridad_actual' => 2,
            'cantidad_requerida' => 4,
            'fecha_entrega' => now()->addDays(30)->toDateString(),
        ]);

        // Act
        $cronograma = app(GestionCronogramaService::class)->proyectar();

        // Assert
        $this->assertSame($primera->id, $cronograma['filas'][0]['orden_id']);
        $this->assertSame($segunda->id, $cronograma['filas'][1]['orden_id']);
        $this->assertSame(1, $cronograma['filas'][0]['etapas'][0]['dias']);
        $this->assertSame(1, $cronograma['filas'][0]['etapas'][1]['dias']);
        $this->assertSame(now()->toDateString(), $cronograma['filas'][0]['etapas'][0]['inicio']);
        $this->assertSame(now()->addDays(2)->toDateString(), $cronograma['filas'][1]['etapas'][0]['inicio']);
    }

    public function test_excludes_closed_and_cancelled_orders(): void
    {
        // Arrange
        $planificacion = $this->planificacion();
        $producto = $this->producto();
        $this->ruta($producto, [60]);

        $this->orden([
            'articulo_id' => $producto->id,
            'planificacion_de_produccion_id' => $planificacion->id,
            'estado' => EstadoOrdenProduccion::Cerrada->value,
        ]);
        $this->orden([
            'articulo_id' => $producto->id,
            'planificacion_de_produccion_id' => $planificacion->id,
            'estado' => EstadoOrdenProduccion::Anulada->value,
        ]);

        // Act
        $cronograma = app(GestionCronogramaService::class)->proyectar();

        // Assert
        $this->assertSame([], $cronograma['filas']);
        $this->assertSame(14, $cronograma['dias']);
    }

    public function test_marks_a_delivery_conflict(): void
    {
        // Arrange
        $planificacion = $this->planificacion();
        $producto = $this->producto();
        $this->ruta($producto, [960, 960]);

        $this->orden([
            'articulo_id' => $producto->id,
            'planificacion_de_produccion_id' => $planificacion->id,
            'cantidad_requerida' => 1,
            'fecha_entrega' => now()->toDateString(),
        ]);

        // Act
        $fila = app(GestionCronogramaService::class)->proyectar()['filas'][0];

        // Assert
        $this->assertTrue($fila['conflicto_entrega']);
        $this->assertTrue($fila['reubicable']);
    }

    public function test_in_progress_orders_are_not_relocatable_and_use_the_remaining_quantity(): void
    {
        // Arrange
        $planificacion = $this->planificacion();
        $producto = $this->producto();
        $this->ruta($producto, [480]);

        $orden = $this->orden([
            'articulo_id' => $producto->id,
            'planificacion_de_produccion_id' => $planificacion->id,
            'estado' => EstadoOrdenProduccion::EnCurso->value,
            'cantidad_requerida' => 10,
            'fecha_entrega' => now()->addDays(30)->toDateString(),
        ]);

        $etapaId = AsignacionDeEtapa::query()->where('articulo_id', $producto->id)->value('etapa_id');

        ParteDiario::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $etapaId,
            'fecha' => now()->toDateString(),
            'cantidad_conformada' => 6,
        ]);

        // Act
        $fila = app(GestionCronogramaService::class)->proyectar()['filas'][0];

        // Assert
        $this->assertFalse($fila['reubicable']);
        $this->assertSame(4, $fila['etapas'][0]['dias']);
    }

    public function test_reordering_persists_priority_and_creates_a_registro(): void
    {
        // Arrange
        $actor = $this->actor();
        $planificacion = $this->planificacion();
        $producto = $this->producto();
        $this->ruta($producto, [60]);

        $orden = $this->orden([
            'articulo_id' => $producto->id,
            'planificacion_de_produccion_id' => $planificacion->id,
            'prioridad_actual' => 5,
        ]);

        // Act
        app(GestionCronogramaService::class)->reordenar(
            $actor,
            $orden,
            1,
            MotivoCambioPrioridad::UrgenciaComercial->value,
            'Cliente nuevo',
            false,
            '127.0.0.1',
        );

        // Assert
        $this->assertSame(1, (int) $orden->refresh()->prioridad_actual);
        $this->assertDatabaseHas('registros_de_prioridad', [
            'orden_de_produccion_id' => $orden->id,
            'usuario_id' => $actor->id,
            'prioridad_anterior' => 5,
            'prioridad_nueva' => 1,
        ]);
        $this->assertDatabaseHas('eventos_de_auditoria', ['accion' => 'reordenar_orden_produccion']);
    }

    public function test_cannot_reorder_an_in_progress_order(): void
    {
        // Arrange
        $actor = $this->actor();
        $orden = $this->orden(['estado' => EstadoOrdenProduccion::EnCurso->value]);

        // Act / Assert
        $this->expectException(ValidationException::class);

        app(GestionCronogramaService::class)->reordenar(
            $actor,
            $orden,
            1,
            MotivoCambioPrioridad::Otro->value,
            null,
            false,
        );
    }

    public function test_rejects_a_priority_below_one(): void
    {
        // Arrange
        $actor = $this->actor();
        $orden = $this->orden([]);

        // Act / Assert
        $this->expectException(ValidationException::class);

        app(GestionCronogramaService::class)->reordenar(
            $actor,
            $orden,
            0,
            MotivoCambioPrioridad::Otro->value,
            null,
            false,
        );
    }

    public function test_rejects_an_unknown_motive(): void
    {
        // Arrange
        $actor = $this->actor();
        $orden = $this->orden([]);

        // Act / Assert
        $this->expectException(ValidationException::class);

        app(GestionCronogramaService::class)->reordenar(
            $actor,
            $orden,
            1,
            'Motivo inventado',
            null,
            false,
        );
    }

    public function test_new_delivery_conflict_blocks_reordering_without_confirmation(): void
    {
        // Arrange
        $actor = $this->actor();
        $planificacion = $this->planificacion();
        $producto = $this->producto();
        $this->ruta($producto, [480]);

        $urgente = $this->orden([
            'articulo_id' => $producto->id,
            'planificacion_de_produccion_id' => $planificacion->id,
            'prioridad_actual' => 1,
            'cantidad_requerida' => 1,
            'fecha_entrega' => now()->addDay()->toDateString(),
        ]);
        $this->orden([
            'articulo_id' => $producto->id,
            'planificacion_de_produccion_id' => $planificacion->id,
            'prioridad_actual' => 10,
            'cantidad_requerida' => 3,
            'fecha_entrega' => now()->addDays(30)->toDateString(),
        ]);

        // Act / Assert
        $this->expectException(ValidationException::class);

        app(GestionCronogramaService::class)->reordenar(
            $actor,
            $urgente,
            20,
            MotivoCambioPrioridad::DecisionGerencial->value,
            null,
            false,
        );
    }

    public function test_confirmed_conflict_persists_the_reordering(): void
    {
        // Arrange
        $actor = $this->actor();
        $planificacion = $this->planificacion();
        $producto = $this->producto();
        $this->ruta($producto, [480]);

        $urgente = $this->orden([
            'articulo_id' => $producto->id,
            'planificacion_de_produccion_id' => $planificacion->id,
            'prioridad_actual' => 1,
            'cantidad_requerida' => 1,
            'fecha_entrega' => now()->addDay()->toDateString(),
        ]);
        $this->orden([
            'articulo_id' => $producto->id,
            'planificacion_de_produccion_id' => $planificacion->id,
            'prioridad_actual' => 10,
            'cantidad_requerida' => 3,
            'fecha_entrega' => now()->addDays(30)->toDateString(),
        ]);

        // Act
        app(GestionCronogramaService::class)->reordenar(
            $actor,
            $urgente,
            20,
            MotivoCambioPrioridad::DecisionGerencial->value,
            null,
            true,
        );

        // Assert
        $this->assertSame(20, (int) $urgente->refresh()->prioridad_actual);
        $this->assertDatabaseHas('registros_de_prioridad', [
            'orden_de_produccion_id' => $urgente->id,
            'prioridad_nueva' => 20,
        ]);
    }
}
