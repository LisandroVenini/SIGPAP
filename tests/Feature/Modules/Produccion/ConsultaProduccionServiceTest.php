<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Inventario\Models\Articulo;
use App\Modules\Produccion\Models\AsignacionDeEtapa;
use App\Modules\Produccion\Models\Etapa;
use App\Modules\Produccion\Models\Maquinaria;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Models\ParteDiario;
use App\Modules\Produccion\Services\ConsultaProduccionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsultaProduccionServiceTest extends TestCase
{
    use RefreshDatabase;

    private function servicio(): ConsultaProduccionService
    {
        return app(ConsultaProduccionService::class);
    }

    public function test_it_returns_only_the_orders_that_still_occupy_the_schedule(): void
    {
        $articulo = Articulo::factory()->create();

        $activa = OrdenDeProduccion::factory()->create([
            'articulo_id' => $articulo->id,
            'estado' => 'En Curso',
            'cantidad_requerida' => 100,
            'cantidad_producida' => 40,
            'prioridad_actual' => 2,
        ]);

        OrdenDeProduccion::factory()->create([
            'articulo_id' => $articulo->id,
            'estado' => 'Cerrada',
        ]);

        OrdenDeProduccion::factory()->create([
            'articulo_id' => $articulo->id,
            'estado' => 'Anulada',
        ]);

        $datos = $this->servicio()->datosDeProduccion();

        $this->assertSame([$activa->id], array_column($datos['ordenes'], 'id'));
        $this->assertSame([
            'id' => $activa->id,
            'articulo_id' => $articulo->id,
            'estado' => 'En Curso',
            'cantidad_requerida' => 100.0,
            'cantidad_producida' => 40.0,
            'fecha_entrega' => $activa->fecha_entrega?->toDateString(),
            'fecha_inicio' => null,
            'fecha_fin' => null,
            'prioridad_actual' => 2,
        ], $datos['ordenes'][0]);
    }

    public function test_it_returns_each_product_route_sheet_with_its_standard_time(): void
    {
        $articulo = Articulo::factory()->create();
        $lijado = Etapa::factory()->create(['nombre' => 'Lijado']);
        $pintura = Etapa::factory()->create(['nombre' => 'Pintura']);

        AsignacionDeEtapa::factory()->create([
            'articulo_id' => $articulo->id,
            'etapa_id' => $pintura->id,
            'tiempo_por_unidad' => 12.5,
            'orden' => 2,
        ]);

        AsignacionDeEtapa::factory()->create([
            'articulo_id' => $articulo->id,
            'etapa_id' => $lijado->id,
            'tiempo_por_unidad' => 4,
            'orden' => 1,
        ]);

        OrdenDeProduccion::factory()->create(['articulo_id' => $articulo->id]);

        $datos = $this->servicio()->datosDeProduccion();

        $this->assertSame([
            ['etapa_id' => $lijado->id, 'etapa' => 'Lijado', 'tiempo_por_unidad' => 4.0],
            ['etapa_id' => $pintura->id, 'etapa' => 'Pintura', 'tiempo_por_unidad' => 12.5],
        ], $datos['rutas'][$articulo->id]);
    }

    public function test_it_returns_the_daily_parts_of_the_active_orders_grouped_by_order(): void
    {
        $articulo = Articulo::factory()->create();
        $etapa = Etapa::factory()->create(['nombre' => 'Pintura']);
        $maquinaria = Maquinaria::factory()->create(['nombre' => 'Pintura 1']);

        $orden = OrdenDeProduccion::factory()->create([
            'articulo_id' => $articulo->id,
            'estado' => 'En Curso',
        ]);

        ParteDiario::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $etapa->id,
            'maquinaria_id' => $maquinaria->id,
            'fecha' => '2026-11-02',
            'cantidad_conformada' => 40,
        ]);

        ParteDiario::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $etapa->id,
            'maquinaria_id' => null,
            'fecha' => '2026-11-03',
            'cantidad_conformada' => 35.5,
        ]);

        $datos = $this->servicio()->datosDeProduccion();

        $this->assertSame([
            [
                'etapa_id' => $etapa->id,
                'etapa' => 'Pintura',
                'fecha' => '2026-11-03',
                'cantidad_conformada' => 35.5,
                'maquinaria' => null,
            ],
            [
                'etapa_id' => $etapa->id,
                'etapa' => 'Pintura',
                'fecha' => '2026-11-02',
                'cantidad_conformada' => 40.0,
                'maquinaria' => 'Pintura 1',
            ],
        ], $datos['partes'][$orden->id]);
    }

    public function test_it_does_not_return_parts_of_orders_outside_the_schedule(): void
    {
        $articulo = Articulo::factory()->create();
        $etapa = Etapa::factory()->create();

        $cerrada = OrdenDeProduccion::factory()->create([
            'articulo_id' => $articulo->id,
            'estado' => 'Cerrada',
        ]);

        ParteDiario::factory()->create([
            'orden_de_produccion_id' => $cerrada->id,
            'etapa_id' => $etapa->id,
            'fecha' => '2026-11-02',
        ]);

        $datos = $this->servicio()->datosDeProduccion();

        $this->assertArrayNotHasKey($cerrada->id, $datos['partes']);
    }

    public function test_it_returns_the_machines_with_their_accumulated_wear(): void
    {
        $maquinaria = Maquinaria::factory()->create([
            'nombre' => 'Lijadora 3',
            'tipo' => 'Lijadora',
            'estado_operativo' => 'Disponible',
            'desgaste_acumulado' => 120.5,
        ]);

        $datos = $this->servicio()->datosDeProduccion();

        $this->assertSame([
            [
                'id' => $maquinaria->id,
                'nombre' => 'Lijadora 3',
                'tipo' => 'Lijadora',
                'estado_operativo' => 'Disponible',
                'desgaste_acumulado' => 120.5,
            ],
        ], $datos['maquinaria']);
    }

    public function test_it_returns_empty_collections_when_the_schedule_is_empty(): void
    {
        $datos = $this->servicio()->datosDeProduccion();

        $this->assertSame([], $datos['ordenes']);
        $this->assertSame([], $datos['rutas']);
        $this->assertSame([], $datos['partes']);
        $this->assertSame([], $datos['maquinaria']);
        $this->assertArrayHasKey('generado_en', $datos);
    }
}
