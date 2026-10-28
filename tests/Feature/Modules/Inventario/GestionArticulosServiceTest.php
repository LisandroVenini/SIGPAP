<?php

namespace Tests\Feature\Modules\Inventario;

use App\Modules\Auditoria\Services\AuditoriaService;
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
}
