<?php

namespace Tests\Feature\Modules\Inventario;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Inventario\Enums\EstadoArticulo;
use App\Modules\Inventario\Enums\OrigenArticulo;
use App\Modules\Inventario\Enums\TipoArticulo;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Models\UnidadDeMedida;
use App\Modules\Inventario\Queries\ArticulosQuery;
use App\Modules\Inventario\Services\GestionArticulosService;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\UsuariosService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GestionArticulosServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): GestionArticulosService
    {
        return new GestionArticulosService(new AuditoriaService, new ArticulosQuery, new UsuariosService);
    }

    private function actor(): Usuario
    {
        return Usuario::factory()->create();
    }

    private function unidad(): UnidadDeMedida
    {
        return UnidadDeMedida::factory()->create();
    }

    public function test_crear_registra_stock_cero_y_audita(): void
    {
        // Arrange
        $actor = $this->actor();
        $unidad = $this->unidad();

        // Act
        $articulo = $this->service()->crear($actor, [
            'codigo_sku' => 'MP-001',
            'descripcion' => 'Madera de pino',
            'tipo' => TipoArticulo::MateriaPrima->value,
            'unidad_de_medida_id' => $unidad->id,
            'stock_minimo' => 10,
            'stock_maximo' => 100,
        ], '127.0.0.1');

        // Assert
        $this->assertSame(0.0, (float) $articulo->stock_fisico);
        $this->assertSame('Activo', $articulo->estado);
        $this->assertNull($articulo->origen);
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'usuario_id' => $actor->id,
            'accion' => 'crear_articulo',
        ]);
    }

    public function test_crear_producto_terminado_guarda_origen(): void
    {
        // Arrange
        $actor = $this->actor();
        $unidad = $this->unidad();

        // Act
        $articulo = $this->service()->crear($actor, [
            'codigo_sku' => 'PT-001',
            'descripcion' => 'Silla de pino',
            'tipo' => TipoArticulo::ProductoTerminado->value,
            'origen' => OrigenArticulo::Fabricado->value,
            'unidad_de_medida_id' => $unidad->id,
        ]);

        // Assert
        $this->assertSame(OrigenArticulo::Fabricado->value, $articulo->origen);
    }

    public function test_actualizar_no_altera_el_stock(): void
    {
        // Arrange
        $actor = $this->actor();
        $unidad = $this->unidad();
        $articulo = Articulo::factory()->create(['unidad_de_medida_id' => $unidad->id]);
        $articulo->stock_fisico = 10;
        $articulo->save();

        // Act
        $this->service()->actualizar($actor, $articulo, [
            'descripcion' => 'Madera de pino premium',
            'tipo' => $articulo->tipo,
            'unidad_de_medida_id' => $unidad->id,
            'stock_minimo' => 5,
            'stock_maximo' => 50,
        ], '127.0.0.1');

        // Assert
        $articulo->refresh();
        $this->assertSame('Madera de pino premium', $articulo->descripcion);
        $this->assertSame(10.0, (float) $articulo->stock_fisico);
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'usuario_id' => $actor->id,
            'accion' => 'actualizar_articulo',
        ]);
    }

    public function test_baja_con_stock_requiere_confirmacion(): void
    {
        // Arrange
        $actor = $this->actor();
        $articulo = Articulo::factory()->create();
        $articulo->stock_fisico = 5;
        $articulo->save();

        // Act / Assert
        $this->expectException(ValidationException::class);
        $this->service()->cambiarEstado($actor, $articulo, false);
    }

    public function test_baja_con_stock_confirmada_inactiva_y_audita(): void
    {
        // Arrange
        $actor = $this->actor();
        $articulo = Articulo::factory()->create();
        $articulo->stock_fisico = 5;
        $articulo->save();

        // Act
        $this->service()->cambiarEstado($actor, $articulo, true, '127.0.0.1');

        // Assert
        $this->assertSame('Inactivo', $articulo->refresh()->estado);
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'usuario_id' => $actor->id,
            'accion' => 'cambiar_estado_articulo',
        ]);
    }

    public function test_listar_filtra_y_resumen_agrega(): void
    {
        // Arrange
        Articulo::factory()->create(['descripcion' => 'Madera', 'tipo' => TipoArticulo::MateriaPrima->value]);
        Articulo::factory()->create(['descripcion' => 'Barniz', 'tipo' => TipoArticulo::InsumoConsumible->value]);

        $inactivo = Articulo::factory()->create(['descripcion' => 'Clavo', 'tipo' => TipoArticulo::InsumoConsumible->value]);
        $inactivo->estado = 'Inactivo';
        $inactivo->save();

        $service = $this->service();

        // Act / Assert
        $this->assertSame(1, $service->listar(['buscar' => 'Madera'])->total());
        $this->assertSame(1, $service->listar(['tipo' => TipoArticulo::MateriaPrima->value])->total());
        $this->assertSame(1, $service->listar(['estado' => 'Inactivo'])->total());
        $this->assertSame(3, $service->listar()->total());

        $resumen = $service->resumen();
        $this->assertSame(3, $resumen['total']);
        $this->assertSame(2, $resumen['activos']);
        $this->assertSame(1, $resumen['inactivos']);
    }

    public function test_productos_terminados_fabricados_filtra_tipo_origen_y_estado(): void
    {
        // Arrange
        $fabricado = Articulo::factory()->create([
            'descripcion' => 'Silla de pino',
            'tipo' => TipoArticulo::ProductoTerminado->value,
            'origen' => OrigenArticulo::Fabricado->value,
        ]);
        Articulo::factory()->create([
            'descripcion' => 'Silla de tercero',
            'tipo' => TipoArticulo::ProductoTerminado->value,
            'origen' => OrigenArticulo::Tercero->value,
        ]);
        Articulo::factory()->create([
            'descripcion' => 'Producto sin origen',
            'tipo' => TipoArticulo::ProductoTerminado->value,
            'origen' => null,
        ]);
        Articulo::factory()->create([
            'descripcion' => 'Tabla de pino',
            'tipo' => TipoArticulo::MateriaPrima->value,
        ]);
        $inactivo = Articulo::factory()->create([
            'descripcion' => 'Silla inactiva',
            'tipo' => TipoArticulo::ProductoTerminado->value,
            'origen' => OrigenArticulo::Fabricado->value,
        ]);
        $inactivo->estado = 'Inactivo';
        $inactivo->save();

        $service = $this->service();

        // Act / Assert
        $this->assertSame([$fabricado->id], $service->productosTerminadosFabricados()->pluck('id')->all());
        $this->assertSame([$fabricado->id], $service->productosTerminadosFabricados('pino')->pluck('id')->all());
        $this->assertSame($fabricado->id, $service->productoTerminadoFabricado($fabricado->id)['id']);
        $this->assertNull($service->productoTerminadoFabricado($inactivo->id));
    }

    public function test_insumos_disponibles_filtra_tipo_y_estado(): void
    {
        // Arrange
        $madera = Articulo::factory()->create([
            'descripcion' => 'Madera de pino',
            'tipo' => TipoArticulo::MateriaPrima->value,
            'estado' => EstadoArticulo::Activo->value,
        ]);
        $barniz = Articulo::factory()->create([
            'descripcion' => 'Barniz',
            'tipo' => TipoArticulo::InsumoConsumible->value,
            'estado' => EstadoArticulo::Activo->value,
        ]);
        Articulo::factory()->create([
            'descripcion' => 'Silla',
            'tipo' => TipoArticulo::ProductoTerminado->value,
            'estado' => EstadoArticulo::Activo->value,
        ]);
        $inactivo = Articulo::factory()->create([
            'descripcion' => 'Clavo inactivo',
            'tipo' => TipoArticulo::MateriaPrima->value,
            'estado' => EstadoArticulo::Activo->value,
        ]);
        $inactivo->estado = EstadoArticulo::Inactivo->value;
        $inactivo->save();

        $service = $this->service();

        // Act
        $insumos = $service->insumosDisponibles();

        // Assert
        $this->assertCount(2, $insumos);
        $ids = $insumos->pluck('id')->all();
        $this->assertContains($madera->id, $ids);
        $this->assertContains($barniz->id, $ids);
        $this->assertNotContains($inactivo->id, $ids);

        // Ordenados por descripción
        $this->assertSame($barniz->id, $insumos->first()['id']);
        $this->assertSame($madera->id, $insumos->last()['id']);
    }

    public function test_insumos_disponibles_busca_por_texto(): void
    {
        // Arrange
        $madera = Articulo::factory()->create([
            'descripcion' => 'Madera de pino',
            'tipo' => TipoArticulo::MateriaPrima->value,
            'estado' => EstadoArticulo::Activo->value,
        ]);
        Articulo::factory()->create([
            'descripcion' => 'Barniz',
            'tipo' => TipoArticulo::InsumoConsumible->value,
            'estado' => EstadoArticulo::Activo->value,
        ]);

        $service = $this->service();

        // Act
        $insumos = $service->insumosDisponibles('pino');

        // Assert
        $this->assertCount(1, $insumos);
        $this->assertSame($madera->id, $insumos->first()['id']);
    }

    public function test_resumenes_de_articulos_resuelve_los_ids_pedidos(): void
    {
        // Arrange
        $madera = Articulo::factory()->create([
            'descripcion' => 'Madera de pino',
            'tipo' => TipoArticulo::MateriaPrima->value,
        ]);
        $silla = Articulo::factory()->create([
            'descripcion' => 'Silla',
            'tipo' => TipoArticulo::ProductoTerminado->value,
        ]);

        $service = $this->service();

        // Act
        $resumenes = $service->resumenesDeArticulos([$madera->id, $silla->id, 9999]);

        // Assert
        $this->assertArrayHasKey($madera->id, $resumenes);
        $this->assertArrayHasKey($silla->id, $resumenes);
        $this->assertArrayNotHasKey(9999, $resumenes);
        $this->assertSame('Madera de pino', $resumenes[$madera->id]['descripcion']);
        $this->assertSame('Silla', $resumenes[$silla->id]['descripcion']);
    }
}
