<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Inventario\Enums\EstadoArticulo;
use App\Modules\Inventario\Enums\EstadoReserva;
use App\Modules\Inventario\Enums\OrigenArticulo;
use App\Modules\Inventario\Enums\TipoArticulo;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Models\Reserva;
use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use App\Modules\Produccion\Enums\ModoDeAltaOrden;
use App\Modules\Produccion\Models\ComponenteDeReceta;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Models\PlanificacionDeProduccion;
use App\Modules\Produccion\Models\Receta;
use App\Modules\Produccion\Services\GestionOrdenesDeProduccionService;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GestionOrdenesDeProduccionServiceTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole(Rol::SupervisorProduccion->value);

        return $usuario;
    }

    private function service(): GestionOrdenesDeProduccionService
    {
        return app(GestionOrdenesDeProduccionService::class);
    }

    private function planificacion(): PlanificacionDeProduccion
    {
        return PlanificacionDeProduccion::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    private function productoFabricado(array $atributos = []): Articulo
    {
        return Articulo::factory()->create($atributos + [
            'tipo' => TipoArticulo::ProductoTerminado->value,
            'origen' => OrigenArticulo::Fabricado->value,
            'estado' => EstadoArticulo::Activo->value,
        ]);
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    private function insumo(array $atributos = []): Articulo
    {
        return Articulo::factory()->create($atributos + [
            'tipo' => TipoArticulo::MateriaPrima->value,
            'estado' => EstadoArticulo::Activo->value,
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

    public function test_creates_covered_order_as_pendiente_with_reservations(): void
    {
        // Arrange
        $actor = $this->actor();
        $planificacion = $this->planificacion();
        $insumo = $this->insumo(['stock_fisico' => 100]);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 10);

        // Act
        $resultado = $this->service()->crear(
            $actor,
            $planificacion->id,
            '2026-11-15',
            [['articulo_id' => $producto->id, 'cantidad' => 5]],
            ModoDeAltaOrden::Todas,
            '127.0.0.1'
        );

        // Assert
        $this->assertSame(['creadas' => 1, 'pendientes' => 0, 'omitidas' => 0], $resultado);
        $this->assertDatabaseHas('ordenes_de_produccion', [
            'articulo_id' => $producto->id,
            'estado' => EstadoOrdenProduccion::Pendiente->value,
            'cantidad_requerida' => 5,
        ]);
        $orden = OrdenDeProduccion::query()->where('articulo_id', $producto->id)->first();
        $this->assertDatabaseHas('reservas', [
            'orden_de_produccion_id' => $orden->id,
            'articulo_id' => $insumo->id,
            'cantidad' => 50,
            'estado' => EstadoReserva::Vigente->value,
        ]);
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'crear_orden_produccion',
            'usuario_id' => $actor->id,
        ]);
    }

    public function test_creates_uncovered_order_as_pendiente_por_insumos_without_reservations(): void
    {
        // Arrange
        $actor = $this->actor();
        $planificacion = $this->planificacion();
        $insumo = $this->insumo(['stock_fisico' => 0]);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 10);

        // Act
        $resultado = $this->service()->crear(
            $actor,
            $planificacion->id,
            '2026-11-15',
            [['articulo_id' => $producto->id, 'cantidad' => 5]],
            ModoDeAltaOrden::Todas,
            '127.0.0.1'
        );

        // Assert
        $this->assertSame(['creadas' => 0, 'pendientes' => 1, 'omitidas' => 0], $resultado);
        $this->assertDatabaseHas('ordenes_de_produccion', [
            'articulo_id' => $producto->id,
            'estado' => EstadoOrdenProduccion::PendientePorInsumos->value,
        ]);
        $orden = OrdenDeProduccion::query()->where('articulo_id', $producto->id)->first();
        $this->assertDatabaseMissing('reservas', [
            'orden_de_produccion_id' => $orden->id,
        ]);
    }

    public function test_solo_disponibles_does_not_create_uncovered_order(): void
    {
        // Arrange
        $actor = $this->actor();
        $planificacion = $this->planificacion();
        $insumo = $this->insumo(['stock_fisico' => 0]);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 10);

        // Act
        $resultado = $this->service()->crear(
            $actor,
            $planificacion->id,
            '2026-11-15',
            [['articulo_id' => $producto->id, 'cantidad' => 5]],
            ModoDeAltaOrden::SoloDisponibles,
            '127.0.0.1'
        );

        // Assert
        $this->assertSame(['creadas' => 0, 'pendientes' => 0, 'omitidas' => 1], $resultado);
        $this->assertDatabaseCount('ordenes_de_produccion', 0);
        $this->assertDatabaseMissing('eventos_de_auditoria', ['accion' => 'crear_orden_produccion']);
    }

    public function test_rejects_product_without_recipe(): void
    {
        // Arrange
        $actor = $this->actor();
        $planificacion = $this->planificacion();
        $producto = $this->productoFabricado();

        // Act / Assert
        $this->expectException(ValidationException::class);

        $this->service()->crear(
            $actor,
            $planificacion->id,
            '2026-11-15',
            [['articulo_id' => $producto->id, 'cantidad' => 5]],
            ModoDeAltaOrden::Todas,
            '127.0.0.1'
        );
    }

    public function test_blocks_anulacion_when_order_is_in_progress(): void
    {
        // Arrange
        $actor = $this->actor();
        $orden = OrdenDeProduccion::factory()->create([
            'estado' => EstadoOrdenProduccion::EnCurso->value,
        ]);

        // Act / Assert
        $this->expectException(ValidationException::class);

        $this->service()->anular($actor, $orden, 'Sin uso', '127.0.0.1');
    }

    public function test_anulacion_releases_reservations(): void
    {
        // Arrange
        $actor = $this->actor();
        $planificacion = $this->planificacion();
        $insumo = $this->insumo(['stock_fisico' => 100]);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 10);
        $this->service()->crear(
            $actor,
            $planificacion->id,
            '2026-11-15',
            [['articulo_id' => $producto->id, 'cantidad' => 5]],
            ModoDeAltaOrden::Todas,
            '127.0.0.1'
        );
        $orden = OrdenDeProduccion::query()->where('articulo_id', $producto->id)->first();

        // Act
        $this->service()->anular($actor, $orden, 'Sin uso', '127.0.0.1');

        // Assert
        $this->assertSame(EstadoOrdenProduccion::Anulada->value, $orden->refresh()->estado);
        $this->assertSame(0, Reserva::query()
            ->where('orden_de_produccion_id', $orden->id)
            ->where('estado', EstadoReserva::Vigente->value)
            ->count());
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'anular_orden_produccion',
            'usuario_id' => $actor->id,
        ]);
    }

    public function test_modificacion_recalculates_needs_and_changes_state(): void
    {
        // Arrange
        $actor = $this->actor();
        $planificacion = $this->planificacion();
        $insumo = $this->insumo(['stock_fisico' => 100]);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 10);
        $this->service()->crear(
            $actor,
            $planificacion->id,
            '2026-11-15',
            [['articulo_id' => $producto->id, 'cantidad' => 5]],
            ModoDeAltaOrden::Todas,
            '127.0.0.1'
        );
        $orden = OrdenDeProduccion::query()->where('articulo_id', $producto->id)->first();

        // Act
        $this->service()->modificar($actor, $orden, 20, '2026-11-20', '127.0.0.1');

        // Assert
        $orden->refresh();
        $this->assertSame(20.0, (float) $orden->cantidad_requerida);
        $this->assertSame('2026-11-20', $orden->fecha_entrega->toDateString());
        $this->assertSame(EstadoOrdenProduccion::PendientePorInsumos->value, $orden->estado);
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'modificar_orden_produccion',
            'usuario_id' => $actor->id,
        ]);
    }

    public function test_lists_orders_with_filters_and_ordering(): void
    {
        // Arrange
        $planificacion = $this->planificacion();
        $productoA = $this->productoFabricado(['descripcion' => 'Silla de pino']);
        $productoB = $this->productoFabricado(['descripcion' => 'Mesa de algarrobo']);
        OrdenDeProduccion::factory()->create([
            'articulo_id' => $productoA->id,
            'planificacion_de_produccion_id' => $planificacion->id,
            'estado' => EstadoOrdenProduccion::Pendiente->value,
            'fecha_entrega' => '2026-11-20',
        ]);
        OrdenDeProduccion::factory()->create([
            'articulo_id' => $productoB->id,
            'planificacion_de_produccion_id' => $planificacion->id,
            'estado' => EstadoOrdenProduccion::PendientePorInsumos->value,
            'fecha_entrega' => '2026-11-10',
        ]);

        // Act
        $todas = $this->service()->listar([]);
        $filtradasPorEstado = $this->service()->listar([
            'estado' => EstadoOrdenProduccion::PendientePorInsumos->value,
        ]);
        $filtradasPorBusqueda = $this->service()->listar(['buscar' => 'Silla']);

        // Assert
        $this->assertSame(2, $todas->total());
        $this->assertSame($productoB->id, $todas->items()[0]['producto']['id']);
        $this->assertSame($productoA->id, $todas->items()[1]['producto']['id']);
        $this->assertSame(1, $filtradasPorEstado->total());
        $this->assertSame($productoB->id, $filtradasPorEstado->items()[0]['producto']['id']);
        $this->assertSame(1, $filtradasPorBusqueda->total());
        $this->assertSame($productoA->id, $filtradasPorBusqueda->items()[0]['producto']['id']);
    }
}
