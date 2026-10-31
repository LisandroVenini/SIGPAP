<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Inventario\Enums\EstadoArticulo;
use App\Modules\Inventario\Enums\OrigenArticulo;
use App\Modules\Inventario\Enums\TipoArticulo;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use App\Modules\Produccion\Models\AsignacionDeEtapa;
use App\Modules\Produccion\Models\ComponenteDeReceta;
use App\Modules\Produccion\Models\Etapa;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Models\ParteDiario;
use App\Modules\Produccion\Models\PlanificacionDeProduccion;
use App\Modules\Produccion\Models\Receta;
use App\Modules\Produccion\Services\GestionCalidadService;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GestionCalidadServiceTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole(Rol::SupervisorProduccion->value);

        return $usuario;
    }

    private function service(): GestionCalidadService
    {
        return app(GestionCalidadService::class);
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

    public function test_registers_quality_control_by_part(): void
    {
        // Arrange
        $actor = $this->actor();
        $insumo = $this->insumo(['stock_fisico' => 100]);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 2);
        [$corte] = $this->hojaDeRutaDeDosEtapas($producto);
        $orden = $this->ordenEnCurso($producto);
        $parte = ParteDiario::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $corte->id,
            'cantidad_conformada' => 8,
        ]);

        // Act
        $control = $this->service()->registrar($actor, $orden, $parte->id, 2, 'Defecto menor', '127.0.0.1');

        // Assert
        $this->assertSame($orden->id, $control->orden_de_produccion_id);
        $this->assertSame($parte->id, $control->parte_diario_id);
        $this->assertSame(6.0, (float) $control->cantidad_aprobada);
        $this->assertSame(2.0, (float) $control->cantidad_rechazada);
        $this->assertSame('Defecto menor', $control->motivo_rechazo);
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'registrar_control_calidad',
            'usuario_id' => $actor->id,
        ]);
    }

    public function test_registers_quality_control_by_order_using_last_stage(): void
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
            'cantidad_conformada' => 9,
        ]);

        // Act
        $control = $this->service()->registrar($actor, $orden, null, 1, 'Defecto final', '127.0.0.1');

        // Assert
        $this->assertNull($control->parte_diario_id);
        $this->assertSame(8.0, (float) $control->cantidad_aprobada);
        $this->assertSame(1.0, (float) $control->cantidad_rechazada);
    }

    public function test_rejects_rejected_greater_than_conformed(): void
    {
        // Arrange
        $actor = $this->actor();
        $producto = $this->productoFabricado();
        [$corte] = $this->hojaDeRutaDeDosEtapas($producto);
        $orden = $this->ordenEnCurso($producto);
        $parte = ParteDiario::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $corte->id,
            'cantidad_conformada' => 5,
        ]);

        // Act / Assert
        $this->expectException(ValidationException::class);

        $this->service()->registrar($actor, $orden, $parte->id, 6, 'Muchos defectos', '127.0.0.1');
    }

    public function test_requires_motivo_when_rejected(): void
    {
        // Arrange
        $actor = $this->actor();
        $producto = $this->productoFabricado();
        [$corte] = $this->hojaDeRutaDeDosEtapas($producto);
        $orden = $this->ordenEnCurso($producto);
        $parte = ParteDiario::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $corte->id,
            'cantidad_conformada' => 5,
        ]);

        // Act / Assert
        $this->expectException(ValidationException::class);

        $this->service()->registrar($actor, $orden, $parte->id, 1, null, '127.0.0.1');
    }

    public function test_rejects_quality_control_when_order_not_in_progress(): void
    {
        // Arrange
        $actor = $this->actor();
        $producto = $this->productoFabricado();
        [$corte] = $this->hojaDeRutaDeDosEtapas($producto);
        $orden = OrdenDeProduccion::factory()->create([
            'articulo_id' => $producto->id,
            'estado' => EstadoOrdenProduccion::Pendiente->value,
            'cantidad_requerida' => 10,
        ]);
        $parte = ParteDiario::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $corte->id,
            'cantidad_conformada' => 5,
        ]);

        // Act / Assert
        $this->expectException(ValidationException::class);

        $this->service()->registrar($actor, $orden, $parte->id, 1, 'Defecto', '127.0.0.1');
    }

    public function test_rejects_part_from_another_order(): void
    {
        // Arrange
        $actor = $this->actor();
        $producto = $this->productoFabricado();
        [$corte] = $this->hojaDeRutaDeDosEtapas($producto);
        $orden = $this->ordenEnCurso($producto);
        $otraOrden = OrdenDeProduccion::factory()->create([
            'articulo_id' => $producto->id,
            'estado' => EstadoOrdenProduccion::EnCurso->value,
        ]);
        $parteDeOtra = ParteDiario::factory()->create([
            'orden_de_produccion_id' => $otraOrden->id,
            'etapa_id' => $corte->id,
            'cantidad_conformada' => 5,
        ]);

        // Act / Assert
        $this->expectException(ValidationException::class);

        $this->service()->registrar($actor, $orden, $parteDeOtra->id, 1, 'Defecto', '127.0.0.1');
    }

    public function test_management_permission_check_returns_true_for_supervisor(): void
    {
        // Arrange
        $actor = $this->actor();

        // Act / Assert
        $this->assertTrue($this->service()->puedeGestionar($actor));
    }
}
