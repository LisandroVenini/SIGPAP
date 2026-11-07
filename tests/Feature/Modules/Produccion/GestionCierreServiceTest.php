<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Inventario\Enums\EstadoArticulo;
use App\Modules\Inventario\Enums\EstadoReserva;
use App\Modules\Inventario\Enums\OrigenArticulo;
use App\Modules\Inventario\Enums\TipoArticulo;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Models\Reserva;
use App\Modules\Inventario\Services\GestionReservasService;
use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use App\Modules\Produccion\Models\AsignacionDeEtapa;
use App\Modules\Produccion\Models\ComponenteDeReceta;
use App\Modules\Produccion\Models\ControlDeCalidad;
use App\Modules\Produccion\Models\Etapa;
use App\Modules\Produccion\Models\Maquinaria;
use App\Modules\Produccion\Models\Operario;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Models\ParteDiario;
use App\Modules\Produccion\Models\PlanificacionDeProduccion;
use App\Modules\Produccion\Models\Receta;
use App\Modules\Produccion\Services\GestionCierreService;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GestionCierreServiceTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole(Rol::SupervisorProduccion->value);

        return $usuario;
    }

    private function service(): GestionCierreService
    {
        return app(GestionCierreService::class);
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

    /**
     * @return array{Etapa, Etapa}
     */
    private function hojaDeRutaDeDosEtapas(Articulo $producto): array
    {
        $corte = Etapa::factory()->create();
        $ensamble = Etapa::factory()->create();

        AsignacionDeEtapa::factory()->create([
            'articulo_id' => $producto->id,
            'etapa_id' => $corte->id,
            'orden' => 1,
        ]);
        AsignacionDeEtapa::factory()->create([
            'articulo_id' => $producto->id,
            'etapa_id' => $ensamble->id,
            'orden' => 2,
        ]);

        return [$corte, $ensamble];
    }

    private function ordenEnCurso(Articulo $producto): OrdenDeProduccion
    {
        $planificacion = PlanificacionDeProduccion::factory()->create();

        return OrdenDeProduccion::factory()->create([
            'articulo_id' => $producto->id,
            'planificacion_de_produccion_id' => $planificacion->id,
            'estado' => EstadoOrdenProduccion::EnCurso->value,
            'cantidad_requerida' => 10,
            'cantidad_producida' => 0,
        ]);
    }

    public function test_closes_order_with_last_stage_control(): void
    {
        // Arrange
        $actor = $this->actor();
        $insumo = $this->insumo(['stock_fisico' => 100]);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 2);
        [$corte, $ensamble] = $this->hojaDeRutaDeDosEtapas($producto);
        $orden = $this->ordenEnCurso($producto);
        $parte = ParteDiario::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $ensamble->id,
            'cantidad_conformada' => 10,
        ]);
        ControlDeCalidad::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'parte_diario_id' => $parte->id,
            'cantidad_aprobada' => 10,
            'cantidad_rechazada' => 0,
        ]);

        // Act
        $this->service()->cerrar($actor, $orden, 10, '127.0.0.1');

        // Assert
        $orden->refresh();
        $this->assertSame(EstadoOrdenProduccion::Cerrada->value, $orden->estado);
        $this->assertSame(10.0, (float) $orden->cantidad_producida);
        $this->assertNotNull($orden->fecha_fin);
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'cerrar_orden_produccion',
            'usuario_id' => $actor->id,
        ]);
    }

    public function test_closes_order_with_order_level_control(): void
    {
        // Arrange
        $actor = $this->actor();
        $insumo = $this->insumo(['stock_fisico' => 100]);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 2);
        [$corte, $ensamble] = $this->hojaDeRutaDeDosEtapas($producto);
        $orden = $this->ordenEnCurso($producto);
        ParteDiario::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $corte->id,
            'cantidad_conformada' => 10,
        ]);
        ParteDiario::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $ensamble->id,
            'cantidad_conformada' => 10,
        ]);
        ControlDeCalidad::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'parte_diario_id' => null,
            'cantidad_aprobada' => 10,
            'cantidad_rechazada' => 0,
        ]);

        // Act
        $this->service()->cerrar($actor, $orden, 10, '127.0.0.1');

        // Assert
        $orden->refresh();
        $this->assertSame(EstadoOrdenProduccion::Cerrada->value, $orden->estado);
        $this->assertSame(10.0, (float) $orden->cantidad_producida);
    }

    public function test_rejects_close_without_quality_control(): void
    {
        // Arrange
        $actor = $this->actor();
        $producto = $this->productoFabricado();
        $this->hojaDeRutaDeDosEtapas($producto);
        $orden = $this->ordenEnCurso($producto);

        // Act / Assert
        try {
            $this->service()->cerrar($actor, $orden, 10, '127.0.0.1');
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('calidad', $e->errors());
        }
    }

    public function test_rejects_close_with_insufficient_approved(): void
    {
        // Arrange
        $actor = $this->actor();
        $producto = $this->productoFabricado();
        [$corte, $ensamble] = $this->hojaDeRutaDeDosEtapas($producto);
        $orden = $this->ordenEnCurso($producto);
        $parte = ParteDiario::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $ensamble->id,
            'cantidad_conformada' => 8,
        ]);
        ControlDeCalidad::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'parte_diario_id' => $parte->id,
            'cantidad_aprobada' => 8,
            'cantidad_rechazada' => 0,
        ]);

        // Act / Assert
        try {
            $this->service()->cerrar($actor, $orden, 10, '127.0.0.1');
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('unidades_finales', $e->errors());
            $this->assertStringContainsString('2', $e->errors()['unidades_finales'][0]);
        }
    }

    public function test_rejects_close_when_order_not_in_progress(): void
    {
        // Arrange
        $actor = $this->actor();
        $producto = $this->productoFabricado();
        [$corte, $ensamble] = $this->hojaDeRutaDeDosEtapas($producto);
        $orden = OrdenDeProduccion::factory()->create([
            'articulo_id' => $producto->id,
            'estado' => EstadoOrdenProduccion::Asignada->value,
            'cantidad_requerida' => 10,
        ]);
        $parte = ParteDiario::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $ensamble->id,
            'cantidad_conformada' => 10,
        ]);
        ControlDeCalidad::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'parte_diario_id' => $parte->id,
            'cantidad_aprobada' => 10,
            'cantidad_rechazada' => 0,
        ]);

        // Act / Assert
        $this->expectException(ValidationException::class);

        $this->service()->cerrar($actor, $orden, 10, '127.0.0.1');
    }

    public function test_postpones_order_releasing_reservations(): void
    {
        // Arrange
        $actor = $this->actor();
        $insumo = $this->insumo(['stock_fisico' => 100]);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 2);
        $this->hojaDeRutaDeDosEtapas($producto);
        $orden = $this->ordenEnCurso($producto);
        app(GestionReservasService::class)->reservar($orden->id, [$insumo->id => 20]);

        // Act
        $this->service()->posponer($actor, $orden, '2026-12-01', true, 'Falta personal', '127.0.0.1');

        // Assert
        $orden->refresh();
        $this->assertSame(EstadoOrdenProduccion::Pospuesta->value, $orden->estado);
        $this->assertSame('2026-12-01', $orden->fecha_prevista_inicio?->toDateString());
        $this->assertSame(0, Reserva::query()
            ->where('orden_de_produccion_id', $orden->id)
            ->where('estado', EstadoReserva::Vigente->value)
            ->count());
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'posponer_orden_produccion',
            'usuario_id' => $actor->id,
        ]);
    }

    public function test_postpones_order_retaining_reservations(): void
    {
        // Arrange
        $actor = $this->actor();
        $insumo = $this->insumo(['stock_fisico' => 100]);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 2);
        $this->hojaDeRutaDeDosEtapas($producto);
        $orden = $this->ordenEnCurso($producto);
        app(GestionReservasService::class)->reservar($orden->id, [$insumo->id => 20]);

        // Act
        $this->service()->posponer($actor, $orden, null, false, null, '127.0.0.1');

        // Assert
        $orden->refresh();
        $this->assertSame(EstadoOrdenProduccion::Pospuesta->value, $orden->estado);
        $this->assertNull($orden->fecha_prevista_inicio);
        $this->assertDatabaseHas('reservas', [
            'orden_de_produccion_id' => $orden->id,
            'articulo_id' => $insumo->id,
            'cantidad' => 20,
            'estado' => EstadoReserva::Vigente->value,
        ]);
    }

    public function test_rejects_postpone_invalid_state(): void
    {
        // Arrange
        $actor = $this->actor();
        $producto = $this->productoFabricado();
        $orden = OrdenDeProduccion::factory()->create([
            'articulo_id' => $producto->id,
            'estado' => EstadoOrdenProduccion::Cerrada->value,
        ]);

        // Act / Assert
        $this->expectException(ValidationException::class);

        $this->service()->posponer($actor, $orden, null, false, null, '127.0.0.1');
    }

    public function test_resumes_retained_reservations_to_in_progress(): void
    {
        // Arrange
        $actor = $this->actor();
        $insumo = $this->insumo(['stock_fisico' => 100]);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 2);
        [$corte] = $this->hojaDeRutaDeDosEtapas($producto);
        $orden = OrdenDeProduccion::factory()->create([
            'articulo_id' => $producto->id,
            'estado' => EstadoOrdenProduccion::Pospuesta->value,
            'cantidad_requerida' => 10,
            'fecha_inicio' => '2026-11-01',
        ]);
        app(GestionReservasService::class)->reservar($orden->id, [$insumo->id => 20]);
        ParteDiario::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $corte->id,
            'cantidad_conformada' => 4,
        ]);

        // Act
        $resultado = $this->service()->reanudar($actor, $orden, '127.0.0.1');

        // Assert
        $orden->refresh();
        $this->assertSame(EstadoOrdenProduccion::EnCurso->value, $orden->estado);
        $this->assertSame([], $resultado['faltantes']);
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'reanudar_orden_produccion',
            'usuario_id' => $actor->id,
        ]);
    }

    public function test_resumes_released_reservations_with_stock(): void
    {
        // Arrange
        $actor = $this->actor();
        $insumo = $this->insumo(['stock_fisico' => 100]);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 2);
        [$corte] = $this->hojaDeRutaDeDosEtapas($producto);
        $orden = OrdenDeProduccion::factory()->create([
            'articulo_id' => $producto->id,
            'estado' => EstadoOrdenProduccion::Pospuesta->value,
            'cantidad_requerida' => 10,
            'fecha_inicio' => '2026-11-01',
        ]);
        ParteDiario::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $corte->id,
            'cantidad_conformada' => 4,
        ]);

        // Act
        $resultado = $this->service()->reanudar($actor, $orden, '127.0.0.1');

        // Assert
        $orden->refresh();
        $this->assertSame(EstadoOrdenProduccion::EnCurso->value, $orden->estado);
        $this->assertSame([], $resultado['faltantes']);
        $this->assertDatabaseHas('reservas', [
            'orden_de_produccion_id' => $orden->id,
            'articulo_id' => $insumo->id,
            'cantidad' => 12,
            'estado' => EstadoReserva::Vigente->value,
        ]);
    }

    public function test_resumes_released_reservations_without_stock(): void
    {
        // Arrange
        $actor = $this->actor();
        $insumo = $this->insumo(['stock_fisico' => 0]);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 2);
        [$corte] = $this->hojaDeRutaDeDosEtapas($producto);
        $orden = OrdenDeProduccion::factory()->create([
            'articulo_id' => $producto->id,
            'estado' => EstadoOrdenProduccion::Pospuesta->value,
            'cantidad_requerida' => 10,
            'fecha_inicio' => '2026-11-01',
        ]);
        ParteDiario::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $corte->id,
            'cantidad_conformada' => 4,
        ]);

        // Act
        $resultado = $this->service()->reanudar($actor, $orden, '127.0.0.1');

        // Assert
        $orden->refresh();
        $this->assertSame(EstadoOrdenProduccion::PendientePorInsumos->value, $orden->estado);
        $this->assertArrayHasKey($insumo->id, $resultado['faltantes']);
        $this->assertSame(12.0, $resultado['faltantes'][$insumo->id]);
    }

    public function test_resumes_without_production_to_assigned(): void
    {
        // Arrange
        $actor = $this->actor();
        $insumo = $this->insumo(['stock_fisico' => 100]);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 2);
        $this->hojaDeRutaDeDosEtapas($producto);
        $orden = OrdenDeProduccion::factory()->create([
            'articulo_id' => $producto->id,
            'estado' => EstadoOrdenProduccion::Pospuesta->value,
            'cantidad_requerida' => 10,
            'fecha_inicio' => null,
        ]);
        $operario = Operario::factory()->create();
        $maquinaria = Maquinaria::factory()->create();
        $orden->operarios()->attach($operario);
        $orden->maquinarias()->attach($maquinaria);
        app(GestionReservasService::class)->reservar($orden->id, [$insumo->id => 20]);

        // Act
        $resultado = $this->service()->reanudar($actor, $orden, '127.0.0.1');

        // Assert
        $orden->refresh();
        $this->assertSame(EstadoOrdenProduccion::Asignada->value, $orden->estado);
        $this->assertSame([], $resultado['faltantes']);
    }

    public function test_resumes_without_production_and_resources_to_pendiente(): void
    {
        // Arrange
        $actor = $this->actor();
        $insumo = $this->insumo(['stock_fisico' => 100]);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 2);
        $this->hojaDeRutaDeDosEtapas($producto);
        $orden = OrdenDeProduccion::factory()->create([
            'articulo_id' => $producto->id,
            'estado' => EstadoOrdenProduccion::Pospuesta->value,
            'cantidad_requerida' => 10,
            'fecha_inicio' => null,
        ]);
        app(GestionReservasService::class)->reservar($orden->id, [$insumo->id => 20]);

        // Act
        $resultado = $this->service()->reanudar($actor, $orden, '127.0.0.1');

        // Assert
        $orden->refresh();
        $this->assertSame(EstadoOrdenProduccion::Pendiente->value, $orden->estado);
        $this->assertSame([], $resultado['faltantes']);
    }

    public function test_rejects_resume_when_not_postponed(): void
    {
        // Arrange
        $actor = $this->actor();
        $producto = $this->productoFabricado();
        $orden = OrdenDeProduccion::factory()->create([
            'articulo_id' => $producto->id,
            'estado' => EstadoOrdenProduccion::EnCurso->value,
        ]);

        // Act / Assert
        $this->expectException(ValidationException::class);

        $this->service()->reanudar($actor, $orden, '127.0.0.1');
    }

    public function test_management_permission_check_returns_true_for_supervisor(): void
    {
        // Arrange
        $actor = $this->actor();

        // Act / Assert
        $this->assertTrue($this->service()->puedeGestionar($actor));
    }

    public function test_closes_order_when_approved_matches_required_with_multiple_last_stage_parts(): void
    {
        // Arrange
        $actor = $this->actor();
        $insumo = $this->insumo(['stock_fisico' => 100]);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 2);
        [$corte, $ensamble] = $this->hojaDeRutaDeDosEtapas($producto);
        $orden = $this->ordenEnCurso($producto);

        ParteDiario::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $corte->id,
            'cantidad_conformada' => 10,
        ]);

        $partePrimera = ParteDiario::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $ensamble->id,
            'fecha' => '2026-10-29',
            'cantidad_conformada' => 4,
        ]);
        $parteSegunda = ParteDiario::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $ensamble->id,
            'fecha' => '2026-10-30',
            'cantidad_conformada' => 4,
        ]);
        $parteTercera = ParteDiario::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $ensamble->id,
            'fecha' => '2026-10-31',
            'cantidad_conformada' => 2,
        ]);

        ControlDeCalidad::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'parte_diario_id' => $partePrimera->id,
            'fecha' => '2026-10-29',
            'cantidad_aprobada' => 4,
            'cantidad_rechazada' => 0,
        ]);
        ControlDeCalidad::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'parte_diario_id' => $parteSegunda->id,
            'fecha' => '2026-10-30',
            'cantidad_aprobada' => 4,
            'cantidad_rechazada' => 0,
        ]);
        ControlDeCalidad::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'parte_diario_id' => $parteTercera->id,
            'fecha' => '2026-10-31',
            'cantidad_aprobada' => 2,
            'cantidad_rechazada' => 0,
        ]);

        // Act
        $this->service()->cerrar($actor, $orden, 10, '127.0.0.1');

        // Assert
        $orden->refresh();
        $this->assertSame(EstadoOrdenProduccion::Cerrada->value, $orden->estado);
        $this->assertSame(10.0, (float) $orden->cantidad_producida);
    }

    public function test_rejects_close_with_correct_shortfall_when_multiple_last_stage_parts_have_rejections(): void
    {
        // Arrange
        $actor = $this->actor();
        $insumo = $this->insumo(['stock_fisico' => 100]);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 2);
        [$corte, $ensamble] = $this->hojaDeRutaDeDosEtapas($producto);
        $orden = $this->ordenEnCurso($producto);

        ParteDiario::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $corte->id,
            'cantidad_conformada' => 10,
        ]);

        $partePrimera = ParteDiario::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $ensamble->id,
            'fecha' => '2026-10-29',
            'cantidad_conformada' => 5,
        ]);
        $parteSegunda = ParteDiario::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $ensamble->id,
            'fecha' => '2026-10-30',
            'cantidad_conformada' => 5,
        ]);

        ControlDeCalidad::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'parte_diario_id' => $partePrimera->id,
            'fecha' => '2026-10-29',
            'cantidad_aprobada' => 4,
            'cantidad_rechazada' => 1,
        ]);
        ControlDeCalidad::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'parte_diario_id' => $parteSegunda->id,
            'fecha' => '2026-10-30',
            'cantidad_aprobada' => 5,
            'cantidad_rechazada' => 0,
        ]);

        // Act / Assert
        try {
            $this->service()->cerrar($actor, $orden, 10, '127.0.0.1');
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('unidades_finales', $e->errors());
            $this->assertStringContainsString('1', $e->errors()['unidades_finales'][0]);
        }
    }

    public function test_order_level_control_overrides_part_controls_for_closure(): void
    {
        // Arrange
        $actor = $this->actor();
        $insumo = $this->insumo(['stock_fisico' => 100]);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 2);
        [$corte, $ensamble] = $this->hojaDeRutaDeDosEtapas($producto);
        $orden = $this->ordenEnCurso($producto);

        ParteDiario::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $corte->id,
            'cantidad_conformada' => 10,
        ]);

        $parte = ParteDiario::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $ensamble->id,
            'cantidad_conformada' => 10,
        ]);

        ControlDeCalidad::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'parte_diario_id' => $parte->id,
            'fecha' => '2026-10-30',
            'cantidad_aprobada' => 6,
            'cantidad_rechazada' => 4,
        ]);
        ControlDeCalidad::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'parte_diario_id' => null,
            'fecha' => '2026-10-31',
            'cantidad_aprobada' => 10,
            'cantidad_rechazada' => 0,
        ]);

        // Act
        $this->service()->cerrar($actor, $orden, 10, '127.0.0.1');

        // Assert
        $orden->refresh();
        $this->assertSame(EstadoOrdenProduccion::Cerrada->value, $orden->estado);
        $this->assertSame(10.0, (float) $orden->cantidad_producida);
    }
}
