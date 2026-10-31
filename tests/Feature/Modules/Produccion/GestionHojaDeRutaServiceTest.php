<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Inventario\Enums\EstadoArticulo;
use App\Modules\Inventario\Enums\OrigenArticulo;
use App\Modules\Inventario\Enums\TipoArticulo;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use App\Modules\Produccion\Models\AsignacionDeEtapa;
use App\Modules\Produccion\Models\Etapa;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Services\GestionHojaDeRutaService;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GestionHojaDeRutaServiceTest extends TestCase
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

    public function test_lists_fabricado_products_with_their_assigned_etapas_count(): void
    {
        // Arrange
        $producto = $this->productoFabricado();
        $etapa = Etapa::factory()->create();
        AsignacionDeEtapa::factory()->create([
            'articulo_id' => $producto->id,
            'etapa_id' => $etapa->id,
            'tiempo_por_unidad' => 10,
        ]);

        // Act
        $productos = app(GestionHojaDeRutaService::class)->productos();

        // Assert
        $this->assertCount(1, $productos);
        $this->assertSame(1, $productos->first()['etapas']);
    }

    public function test_filters_fabricado_products_by_search_term(): void
    {
        // Arrange
        $silla = $this->productoFabricado(['descripcion' => 'Silla de pino']);
        $this->productoFabricado(['descripcion' => 'Mesa de algarrobo']);

        // Act
        $productos = app(GestionHojaDeRutaService::class)->productos(['buscar' => 'Silla']);

        // Assert
        $this->assertCount(1, $productos);
        $this->assertSame($silla->id, $productos->first()['id']);
    }

    public function test_assigns_an_etapa_to_a_fabricado_product(): void
    {
        // Arrange
        $actor = $this->actor();
        $producto = $this->productoFabricado();
        $etapa = Etapa::factory()->create();

        // Act
        app(GestionHojaDeRutaService::class)->asignar($actor, $producto->id, $etapa->id, 12.5, '127.0.0.1');

        // Assert
        $this->assertDatabaseHas('asignaciones_de_etapa', [
            'articulo_id' => $producto->id,
            'etapa_id' => $etapa->id,
            'tiempo_por_unidad' => 12.5,
            'orden' => 1,
        ]);
        $this->assertDatabaseHas('eventos_de_auditoria', ['accion' => 'asignar_etapa_producto']);
    }

    public function test_assigning_three_etapas_assigns_increasing_order(): void
    {
        // Arrange
        $actor = $this->actor();
        $producto = $this->productoFabricado();
        $etapaUno = Etapa::factory()->create();
        $etapaDos = Etapa::factory()->create();
        $etapaTres = Etapa::factory()->create();

        // Act
        $service = app(GestionHojaDeRutaService::class);
        $service->asignar($actor, $producto->id, $etapaUno->id, 10, '127.0.0.1');
        $service->asignar($actor, $producto->id, $etapaDos->id, 20, '127.0.0.1');
        $service->asignar($actor, $producto->id, $etapaTres->id, 30, '127.0.0.1');

        // Assert
        $this->assertSame(1, (int) AsignacionDeEtapa::query()->where('etapa_id', $etapaUno->id)->value('orden'));
        $this->assertSame(2, (int) AsignacionDeEtapa::query()->where('etapa_id', $etapaDos->id)->value('orden'));
        $this->assertSame(3, (int) AsignacionDeEtapa::query()->where('etapa_id', $etapaTres->id)->value('orden'));
    }

    public function test_desassigning_the_middle_etapa_reindexes_remaining_to_one_and_two(): void
    {
        // Arrange
        $actor = $this->actor();
        $producto = $this->productoFabricado();
        $etapaUno = Etapa::factory()->create();
        $etapaDos = Etapa::factory()->create();
        $etapaTres = Etapa::factory()->create();
        AsignacionDeEtapa::factory()->create([
            'articulo_id' => $producto->id,
            'etapa_id' => $etapaUno->id,
            'orden' => 1,
        ]);
        AsignacionDeEtapa::factory()->create([
            'articulo_id' => $producto->id,
            'etapa_id' => $etapaDos->id,
            'orden' => 2,
        ]);
        AsignacionDeEtapa::factory()->create([
            'articulo_id' => $producto->id,
            'etapa_id' => $etapaTres->id,
            'orden' => 3,
        ]);

        // Act
        app(GestionHojaDeRutaService::class)->desasignar($actor, $producto->id, $etapaDos->id, '127.0.0.1');

        // Assert
        $this->assertDatabaseMissing('asignaciones_de_etapa', [
            'articulo_id' => $producto->id,
            'etapa_id' => $etapaDos->id,
        ]);
        $this->assertDatabaseHas('asignaciones_de_etapa', [
            'articulo_id' => $producto->id,
            'etapa_id' => $etapaUno->id,
            'orden' => 1,
        ]);
        $this->assertDatabaseHas('asignaciones_de_etapa', [
            'articulo_id' => $producto->id,
            'etapa_id' => $etapaTres->id,
            'orden' => 2,
        ]);
    }

    public function test_moving_an_etapa_down_swaps_order_with_neighbor(): void
    {
        // Arrange
        $actor = $this->actor();
        $producto = $this->productoFabricado();
        $etapaUno = Etapa::factory()->create();
        $etapaDos = Etapa::factory()->create();
        AsignacionDeEtapa::factory()->create([
            'articulo_id' => $producto->id,
            'etapa_id' => $etapaUno->id,
            'orden' => 1,
        ]);
        AsignacionDeEtapa::factory()->create([
            'articulo_id' => $producto->id,
            'etapa_id' => $etapaDos->id,
            'orden' => 2,
        ]);

        // Act
        app(GestionHojaDeRutaService::class)->mover($actor, $producto->id, $etapaUno->id, 'bajar', '127.0.0.1');

        // Assert
        $this->assertSame(2, (int) AsignacionDeEtapa::query()->where('etapa_id', $etapaUno->id)->value('orden'));
        $this->assertSame(1, (int) AsignacionDeEtapa::query()->where('etapa_id', $etapaDos->id)->value('orden'));
        $this->assertDatabaseHas('eventos_de_auditoria', ['accion' => 'mover_etapa_hoja_de_ruta']);
    }

    public function test_moving_an_etapa_up_swaps_order_with_neighbor(): void
    {
        // Arrange
        $actor = $this->actor();
        $producto = $this->productoFabricado();
        $etapaUno = Etapa::factory()->create();
        $etapaDos = Etapa::factory()->create();
        AsignacionDeEtapa::factory()->create([
            'articulo_id' => $producto->id,
            'etapa_id' => $etapaUno->id,
            'orden' => 1,
        ]);
        AsignacionDeEtapa::factory()->create([
            'articulo_id' => $producto->id,
            'etapa_id' => $etapaDos->id,
            'orden' => 2,
        ]);

        // Act
        app(GestionHojaDeRutaService::class)->mover($actor, $producto->id, $etapaDos->id, 'subir', '127.0.0.1');

        // Assert
        $this->assertSame(2, (int) AsignacionDeEtapa::query()->where('etapa_id', $etapaUno->id)->value('orden'));
        $this->assertSame(1, (int) AsignacionDeEtapa::query()->where('etapa_id', $etapaDos->id)->value('orden'));
    }

    public function test_moving_the_first_etapa_up_is_idempotent(): void
    {
        // Arrange
        $actor = $this->actor();
        $producto = $this->productoFabricado();
        $etapaUno = Etapa::factory()->create();
        $etapaDos = Etapa::factory()->create();
        AsignacionDeEtapa::factory()->create([
            'articulo_id' => $producto->id,
            'etapa_id' => $etapaUno->id,
            'orden' => 1,
        ]);
        AsignacionDeEtapa::factory()->create([
            'articulo_id' => $producto->id,
            'etapa_id' => $etapaDos->id,
            'orden' => 2,
        ]);

        // Act
        app(GestionHojaDeRutaService::class)->mover($actor, $producto->id, $etapaUno->id, 'subir', '127.0.0.1');

        // Assert
        $this->assertSame(1, (int) AsignacionDeEtapa::query()->where('etapa_id', $etapaUno->id)->value('orden'));
        $this->assertSame(2, (int) AsignacionDeEtapa::query()->where('etapa_id', $etapaDos->id)->value('orden'));
    }

    public function test_moving_the_last_etapa_down_is_idempotent(): void
    {
        // Arrange
        $actor = $this->actor();
        $producto = $this->productoFabricado();
        $etapaUno = Etapa::factory()->create();
        $etapaDos = Etapa::factory()->create();
        AsignacionDeEtapa::factory()->create([
            'articulo_id' => $producto->id,
            'etapa_id' => $etapaUno->id,
            'orden' => 1,
        ]);
        AsignacionDeEtapa::factory()->create([
            'articulo_id' => $producto->id,
            'etapa_id' => $etapaDos->id,
            'orden' => 2,
        ]);

        // Act
        app(GestionHojaDeRutaService::class)->mover($actor, $producto->id, $etapaDos->id, 'bajar', '127.0.0.1');

        // Assert
        $this->assertSame(1, (int) AsignacionDeEtapa::query()->where('etapa_id', $etapaUno->id)->value('orden'));
        $this->assertSame(2, (int) AsignacionDeEtapa::query()->where('etapa_id', $etapaDos->id)->value('orden'));
    }

    public function test_blocks_assigning_an_etapa_twice(): void
    {
        // Arrange
        $actor = $this->actor();
        $producto = $this->productoFabricado();
        $etapa = Etapa::factory()->create();
        AsignacionDeEtapa::factory()->create([
            'articulo_id' => $producto->id,
            'etapa_id' => $etapa->id,
        ]);

        // Act / Assert
        $this->expectException(ValidationException::class);

        app(GestionHojaDeRutaService::class)->asignar($actor, $producto->id, $etapa->id, 5, '127.0.0.1');
    }

    public function test_blocks_assigning_to_a_product_that_is_not_fabricado(): void
    {
        // Arrange
        $actor = $this->actor();
        $producto = Articulo::factory()->create([
            'tipo' => TipoArticulo::ProductoTerminado->value,
            'origen' => OrigenArticulo::Tercero->value,
            'estado' => EstadoArticulo::Activo->value,
        ]);
        $etapa = Etapa::factory()->create();

        // Act / Assert
        $this->expectException(ValidationException::class);

        app(GestionHojaDeRutaService::class)->asignar($actor, $producto->id, $etapa->id, 5, '127.0.0.1');
    }

    public function test_desassigns_an_etapa_from_a_product(): void
    {
        // Arrange
        $actor = $this->actor();
        $producto = $this->productoFabricado();
        $etapa = Etapa::factory()->create();
        AsignacionDeEtapa::factory()->create([
            'articulo_id' => $producto->id,
            'etapa_id' => $etapa->id,
        ]);

        // Act
        app(GestionHojaDeRutaService::class)->desasignar($actor, $producto->id, $etapa->id, '127.0.0.1');

        // Assert
        $this->assertDatabaseMissing('asignaciones_de_etapa', [
            'articulo_id' => $producto->id,
            'etapa_id' => $etapa->id,
        ]);
        $this->assertDatabaseHas('eventos_de_auditoria', ['accion' => 'desasignar_etapa_producto']);
    }

    public function test_blocks_desassigning_when_an_order_is_in_progress(): void
    {
        // Arrange
        $actor = $this->actor();
        $producto = $this->productoFabricado();
        $etapa = Etapa::factory()->create();
        AsignacionDeEtapa::factory()->create([
            'articulo_id' => $producto->id,
            'etapa_id' => $etapa->id,
        ]);
        OrdenDeProduccion::factory()->create([
            'articulo_id' => $producto->id,
            'estado' => EstadoOrdenProduccion::EnCurso->value,
        ]);

        // Act / Assert
        $this->expectException(ValidationException::class);

        app(GestionHojaDeRutaService::class)->desasignar($actor, $producto->id, $etapa->id, '127.0.0.1');
    }
}
