<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Inventario\Enums\EstadoArticulo;
use App\Modules\Inventario\Enums\OrigenArticulo;
use App\Modules\Inventario\Enums\TipoArticulo;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Models\UnidadDeMedida;
use App\Modules\Produccion\Models\ComponenteDeReceta;
use App\Modules\Produccion\Models\Receta;
use App\Modules\Produccion\Services\GestionRecetasService;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GestionRecetasServiceTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole(Rol::SupervisorProduccion->value);

        return $usuario;
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
    private function insumoActivo(array $atributos = []): Articulo
    {
        return Articulo::factory()->create($atributos + [
            'tipo' => TipoArticulo::MateriaPrima->value,
            'origen' => null,
            'estado' => EstadoArticulo::Activo->value,
        ]);
    }

    public function test_agrega_componente_a_la_receta_y_audita(): void
    {
        // Arrange
        $actor = $this->actor();
        $producto = $this->productoFabricado();
        $insumo = $this->insumoActivo();

        // Act
        $componente = app(GestionRecetasService::class)->agregarComponente(
            $actor,
            $producto->id,
            $insumo->id,
            5.5,
            '127.0.0.1'
        );

        // Assert
        $this->assertInstanceOf(ComponenteDeReceta::class, $componente);
        $this->assertDatabaseHas('componentes_de_receta', [
            'receta_id' => $componente->receta_id,
            'articulo_id' => $insumo->id,
            'cantidad' => 5.5,
        ]);
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'agregar_componente_receta',
            'auditable_type' => Receta::class,
            'auditable_id' => $componente->receta_id,
        ]);
    }

    public function test_rechaza_producto_que_no_es_fabricado(): void
    {
        // Arrange
        $actor = $this->actor();
        $producto = Articulo::factory()->create([
            'tipo' => TipoArticulo::ProductoTerminado->value,
            'origen' => OrigenArticulo::Tercero->value,
            'estado' => EstadoArticulo::Activo->value,
        ]);
        $insumo = $this->insumoActivo();

        // Act / Assert
        $this->expectException(ValidationException::class);

        app(GestionRecetasService::class)->agregarComponente($actor, $producto->id, $insumo->id, 1);
    }

    public function test_rechaza_insumo_que_no_es_materia_prima_ni_insumo_consumible(): void
    {
        // Arrange
        $actor = $this->actor();
        $producto = $this->productoFabricado();
        $noInsumo = Articulo::factory()->create([
            'tipo' => TipoArticulo::ProductoTerminado->value,
            'origen' => OrigenArticulo::Fabricado->value,
            'estado' => EstadoArticulo::Activo->value,
        ]);

        // Act / Assert
        $this->expectException(ValidationException::class);

        app(GestionRecetasService::class)->agregarComponente($actor, $producto->id, $noInsumo->id, 1);
    }

    public function test_rechaza_insumo_inactivo(): void
    {
        // Arrange
        $actor = $this->actor();
        $producto = $this->productoFabricado();
        $inactivo = $this->insumoActivo(['estado' => EstadoArticulo::Inactivo->value]);

        // Act / Assert
        $this->expectException(ValidationException::class);

        app(GestionRecetasService::class)->agregarComponente($actor, $producto->id, $inactivo->id, 1);
    }

    public function test_rechaza_duplicado_en_la_receta(): void
    {
        // Arrange
        $actor = $this->actor();
        $producto = $this->productoFabricado();
        $insumo = $this->insumoActivo();
        $receta = Receta::factory()->create(['articulo_id' => $producto->id]);
        ComponenteDeReceta::factory()->create([
            'receta_id' => $receta->id,
            'articulo_id' => $insumo->id,
            'cantidad' => 2,
        ]);

        // Act / Assert
        try {
            app(GestionRecetasService::class)->agregarComponente($actor, $producto->id, $insumo->id, 3);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('articulo_insumo', $e->errors());
            $this->assertSame('Ese insumo ya está en la receta.', $e->errors()['articulo_insumo'][0]);
        }
    }

    public function test_rechaza_cantidad_menor_o_igual_a_cero(): void
    {
        // Arrange
        $actor = $this->actor();
        $producto = $this->productoFabricado();
        $insumo = $this->insumoActivo();

        // Act / Assert
        $this->expectException(ValidationException::class);

        app(GestionRecetasService::class)->agregarComponente($actor, $producto->id, $insumo->id, 0);
    }

    public function test_detalle_excluye_de_disponibles_los_ya_usados(): void
    {
        // Arrange
        $actor = $this->actor();
        $producto = $this->productoFabricado();
        $usado = $this->insumoActivo(['descripcion' => 'Tablón de pino']);
        $libre = $this->insumoActivo(['descripcion' => 'Barniz natural']);
        $receta = Receta::factory()->create(['articulo_id' => $producto->id]);
        ComponenteDeReceta::factory()->create([
            'receta_id' => $receta->id,
            'articulo_id' => $usado->id,
            'cantidad' => 4,
        ]);

        // Act
        $detalle = app(GestionRecetasService::class)->detalle($producto->id);

        // Assert
        $this->assertCount(1, $detalle['componentes']);
        $this->assertSame($usado->id, $detalle['componentes'][0]['articulo_id']);
        $this->assertSame(4.0, $detalle['componentes'][0]['cantidad']);

        $disponiblesIds = array_column($detalle['disponibles'], 'id');
        $this->assertNotContains($usado->id, $disponiblesIds);
        $this->assertContains($libre->id, $disponiblesIds);
    }

    public function test_quita_componente_de_la_receta_y_audita(): void
    {
        // Arrange
        $actor = $this->actor();
        $producto = $this->productoFabricado();
        $insumo = $this->insumoActivo();
        $receta = Receta::factory()->create(['articulo_id' => $producto->id]);
        $componente = ComponenteDeReceta::factory()->create([
            'receta_id' => $receta->id,
            'articulo_id' => $insumo->id,
            'cantidad' => 3,
        ]);

        // Act
        app(GestionRecetasService::class)->quitarComponente($actor, $producto->id, $componente, '127.0.0.1');

        // Assert
        $this->assertDatabaseMissing('componentes_de_receta', ['id' => $componente->id]);
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'quitar_componente_receta',
            'auditable_type' => Receta::class,
            'auditable_id' => $receta->id,
        ]);
    }

    public function test_detalle_ordena_componentes_por_descripcion(): void
    {
        // Arrange
        $actor = $this->actor();
        $producto = $this->productoFabricado();
        $receta = Receta::factory()->create(['articulo_id' => $producto->id]);
        $barniz = $this->insumoActivo(['descripcion' => 'Barniz natural']);
        $tablon = $this->insumoActivo(['descripcion' => 'Tablón de pino']);
        ComponenteDeReceta::factory()->create([
            'receta_id' => $receta->id,
            'articulo_id' => $tablon->id,
            'cantidad' => 1,
        ]);
        ComponenteDeReceta::factory()->create([
            'receta_id' => $receta->id,
            'articulo_id' => $barniz->id,
            'cantidad' => 1,
        ]);

        // Act
        $detalle = app(GestionRecetasService::class)->detalle($producto->id);

        // Assert
        $this->assertSame($barniz->id, $detalle['componentes'][0]['articulo_id']);
        $this->assertSame($tablon->id, $detalle['componentes'][1]['articulo_id']);
    }

    public function test_detalle_incluye_unidad_del_insumo(): void
    {
        // Arrange
        $actor = $this->actor();
        $producto = $this->productoFabricado();
        $unidad = UnidadDeMedida::factory()->create(['nombre' => 'Mililitro']);
        $insumo = $this->insumoActivo(['unidad_de_medida_id' => $unidad->id]);
        $receta = Receta::factory()->create(['articulo_id' => $producto->id]);
        ComponenteDeReceta::factory()->create([
            'receta_id' => $receta->id,
            'articulo_id' => $insumo->id,
            'cantidad' => 250,
        ]);

        // Act
        $detalle = app(GestionRecetasService::class)->detalle($producto->id);

        // Assert
        $this->assertSame('Mililitro', $detalle['componentes'][0]['unidad']);
    }
}
