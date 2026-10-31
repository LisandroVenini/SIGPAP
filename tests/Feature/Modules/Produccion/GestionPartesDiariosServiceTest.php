<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Inventario\Enums\EstadoArticulo;
use App\Modules\Inventario\Enums\EstadoReserva;
use App\Modules\Inventario\Enums\OrigenArticulo;
use App\Modules\Inventario\Enums\TipoArticulo;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Models\Reserva;
use App\Modules\Inventario\Services\GestionReservasService;
use App\Modules\Produccion\Enums\EstadoMaquinaria;
use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use App\Modules\Produccion\Models\AsignacionDeEtapa;
use App\Modules\Produccion\Models\ComponenteDeReceta;
use App\Modules\Produccion\Models\Etapa;
use App\Modules\Produccion\Models\Maquinaria;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Models\PlanificacionDeProduccion;
use App\Modules\Produccion\Models\Receta;
use App\Modules\Produccion\Services\GestionPartesDiariosService;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GestionPartesDiariosServiceTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole(Rol::SupervisorProduccion->value);

        return $usuario;
    }

    private function service(): GestionPartesDiariosService
    {
        return app(GestionPartesDiariosService::class);
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
            'tiempo_por_unidad' => 10,
        ]);
        AsignacionDeEtapa::factory()->create([
            'articulo_id' => $producto->id,
            'etapa_id' => $ensamble->id,
            'tiempo_por_unidad' => 20,
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

    public function test_registers_parte_in_first_stage_and_consumes_insulation(): void
    {
        // Arrange
        $actor = $this->actor();
        $insumo = $this->insumo(['stock_fisico' => 100]);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 2);
        [$corte] = $this->hojaDeRutaDeDosEtapas($producto);
        $orden = $this->ordenEnCurso($producto);
        app(GestionReservasService::class)->reservar($orden->id, [$insumo->id => 20]);

        // Act
        $parte = $this->service()->registrar(
            $actor,
            $orden,
            $corte->id,
            null,
            '2026-11-01',
            6,
            null,
            '127.0.0.1'
        );

        // Assert
        $this->assertSame($orden->id, $parte->orden_de_produccion_id);
        $this->assertSame($corte->id, $parte->etapa_id);
        $this->assertSame(6.0, (float) $parte->cantidad_conformada);
        $this->assertDatabaseHas('reservas', [
            'orden_de_produccion_id' => $orden->id,
            'articulo_id' => $insumo->id,
            'cantidad' => 12,
            'estado' => EstadoReserva::Consumida->value,
        ]);
        $this->assertDatabaseHas('reservas', [
            'orden_de_produccion_id' => $orden->id,
            'articulo_id' => $insumo->id,
            'cantidad' => 8,
            'estado' => EstadoReserva::Vigente->value,
        ]);
        $this->assertSame(88.0, (float) $insumo->refresh()->stock_fisico);
        $this->assertDatabaseHas('movimientos_de_stock', [
            'articulo_id' => $insumo->id,
            'orden_de_produccion_id' => $orden->id,
            'tipo' => 'Consumo',
            'cantidad' => -12.0,
        ]);
    }

    public function test_registers_parte_in_later_stage_without_consuming(): void
    {
        // Arrange
        $actor = $this->actor();
        $insumo = $this->insumo(['stock_fisico' => 100]);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 2);
        [$corte, $ensamble] = $this->hojaDeRutaDeDosEtapas($producto);
        $orden = $this->ordenEnCurso($producto);
        app(GestionReservasService::class)->reservar($orden->id, [$insumo->id => 20]);

        // Act
        $parte = $this->service()->registrar(
            $actor,
            $orden,
            $ensamble->id,
            null,
            '2026-11-01',
            5,
            null,
            '127.0.0.1'
        );

        // Assert
        $this->assertSame(5.0, (float) $parte->cantidad_conformada);
        $this->assertDatabaseCount('reservas', 1);
        $this->assertDatabaseHas('reservas', [
            'orden_de_produccion_id' => $orden->id,
            'articulo_id' => $insumo->id,
            'cantidad' => 20,
            'estado' => EstadoReserva::Vigente->value,
        ]);
        $this->assertSame(100.0, (float) $insumo->refresh()->stock_fisico);
        $this->assertDatabaseCount('movimientos_de_stock', 0);
    }

    public function test_accumulates_quantity_on_same_day_and_consumes_only_the_delta(): void
    {
        // Arrange
        $actor = $this->actor();
        $insumo = $this->insumo(['stock_fisico' => 100]);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 2);
        [$corte] = $this->hojaDeRutaDeDosEtapas($producto);
        $orden = $this->ordenEnCurso($producto);
        app(GestionReservasService::class)->reservar($orden->id, [$insumo->id => 20]);

        $this->service()->registrar(
            $actor,
            $orden,
            $corte->id,
            null,
            '2026-11-01',
            4,
            null,
            '127.0.0.1'
        );

        // Act
        $parte = $this->service()->registrar(
            $actor,
            $orden,
            $corte->id,
            null,
            '2026-11-01',
            3,
            null,
            '127.0.0.1'
        );

        // Assert
        $this->assertSame(7.0, (float) $parte->cantidad_conformada);
        $this->assertDatabaseCount('partes_diarios', 1);
        $this->assertSame(14.0, (float) Reserva::query()
            ->where('orden_de_produccion_id', $orden->id)
            ->where('articulo_id', $insumo->id)
            ->where('estado', EstadoReserva::Consumida->value)
            ->sum('cantidad'));
        $this->assertDatabaseHas('reservas', [
            'orden_de_produccion_id' => $orden->id,
            'articulo_id' => $insumo->id,
            'cantidad' => 6,
            'estado' => EstadoReserva::Vigente->value,
        ]);
    }

    public function test_increments_machine_wear_by_declared_quantity(): void
    {
        // Arrange
        $actor = $this->actor();
        $insumo = $this->insumo(['stock_fisico' => 100]);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 2);
        [$corte] = $this->hojaDeRutaDeDosEtapas($producto);
        $orden = $this->ordenEnCurso($producto);
        $maquinaria = Maquinaria::factory()->create([
            'estado_operativo' => EstadoMaquinaria::Disponible->value,
            'desgaste_acumulado' => 12.5,
        ]);
        $orden->maquinarias()->attach($maquinaria->id);
        app(GestionReservasService::class)->reservar($orden->id, [$insumo->id => 20]);

        // Act
        $this->service()->registrar(
            $actor,
            $orden,
            $corte->id,
            $maquinaria->id,
            '2026-11-01',
            6,
            null,
            '127.0.0.1'
        );

        // Assert
        $this->assertSame(18.5, (float) $maquinaria->refresh()->desgaste_acumulado);
    }

    public function test_rejects_quantity_exceeding_remaining_for_stage(): void
    {
        // Arrange
        $actor = $this->actor();
        $insumo = $this->insumo(['stock_fisico' => 100]);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 2);
        [$corte] = $this->hojaDeRutaDeDosEtapas($producto);
        $orden = $this->ordenEnCurso($producto);
        app(GestionReservasService::class)->reservar($orden->id, [$insumo->id => 20]);

        // Act / Assert
        try {
            $this->service()->registrar(
                $actor,
                $orden,
                $corte->id,
                null,
                '2026-11-01',
                11,
                null,
                '127.0.0.1'
            );
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('cantidad', $e->errors());
            $this->assertStringContainsString('10', $e->errors()['cantidad'][0]);
        }
    }

    public function test_rejects_stage_outside_route_sheet(): void
    {
        // Arrange
        $actor = $this->actor();
        $insumo = $this->insumo(['stock_fisico' => 100]);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 2);
        $this->hojaDeRutaDeDosEtapas($producto);
        $orden = $this->ordenEnCurso($producto);
        $etapaExterna = Etapa::factory()->create();
        app(GestionReservasService::class)->reservar($orden->id, [$insumo->id => 20]);

        // Act / Assert
        try {
            $this->service()->registrar(
                $actor,
                $orden,
                $etapaExterna->id,
                null,
                '2026-11-01',
                1,
                null,
                '127.0.0.1'
            );
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('etapa', $e->errors());
        }
    }

    public function test_rejects_machine_not_assigned_to_order(): void
    {
        // Arrange
        $actor = $this->actor();
        $insumo = $this->insumo(['stock_fisico' => 100]);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 2);
        [$corte] = $this->hojaDeRutaDeDosEtapas($producto);
        $orden = $this->ordenEnCurso($producto);
        $maquinaria = Maquinaria::factory()->create([
            'estado_operativo' => EstadoMaquinaria::Disponible->value,
        ]);
        app(GestionReservasService::class)->reservar($orden->id, [$insumo->id => 20]);

        // Act / Assert
        try {
            $this->service()->registrar(
                $actor,
                $orden,
                $corte->id,
                $maquinaria->id,
                '2026-11-01',
                1,
                null,
                '127.0.0.1'
            );
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('maquinaria', $e->errors());
        }
    }

    public function test_rejects_order_not_in_progress(): void
    {
        // Arrange
        $actor = $this->actor();
        $insumo = $this->insumo(['stock_fisico' => 100]);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 2);
        [$corte] = $this->hojaDeRutaDeDosEtapas($producto);
        $orden = OrdenDeProduccion::factory()->create([
            'articulo_id' => $producto->id,
            'estado' => EstadoOrdenProduccion::Pendiente->value,
            'cantidad_requerida' => 10,
        ]);
        app(GestionReservasService::class)->reservar($orden->id, [$insumo->id => 20]);

        // Act / Assert
        try {
            $this->service()->registrar(
                $actor,
                $orden,
                $corte->id,
                null,
                '2026-11-01',
                1,
                null,
                '127.0.0.1'
            );
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('estado', $e->errors());
        }
    }

    public function test_management_permission_check_returns_true_for_supervisor(): void
    {
        // Arrange
        $actor = $this->actor();

        // Act / Assert
        $this->assertTrue($this->service()->puedeGestionar($actor));
    }
}
