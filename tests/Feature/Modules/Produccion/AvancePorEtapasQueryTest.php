<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Inventario\Enums\EstadoArticulo;
use App\Modules\Inventario\Enums\OrigenArticulo;
use App\Modules\Inventario\Enums\TipoArticulo;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use App\Modules\Produccion\Models\AsignacionDeEtapa;
use App\Modules\Produccion\Models\ControlDeCalidad;
use App\Modules\Produccion\Models\Etapa;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Models\ParteDiario;
use App\Modules\Produccion\Queries\AvancePorEtapasQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvancePorEtapasQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_produced_and_remaining_units_per_stage_in_route_order(): void
    {
        // Arrange
        $producto = Articulo::factory()->create([
            'tipo' => TipoArticulo::ProductoTerminado->value,
            'origen' => OrigenArticulo::Fabricado->value,
            'estado' => EstadoArticulo::Activo->value,
        ]);
        $corte = Etapa::factory()->create();
        $lijado = Etapa::factory()->create();
        AsignacionDeEtapa::factory()->create([
            'articulo_id' => $producto->id,
            'etapa_id' => $lijado->id,
            'orden' => 2,
        ]);
        AsignacionDeEtapa::factory()->create([
            'articulo_id' => $producto->id,
            'etapa_id' => $corte->id,
            'orden' => 1,
        ]);
        $orden = OrdenDeProduccion::factory()->create([
            'articulo_id' => $producto->id,
            'estado' => EstadoOrdenProduccion::EnCurso->value,
            'cantidad_requerida' => 10,
        ]);
        $parte = ParteDiario::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $corte->id,
            'maquinaria_id' => null,
            'cantidad_conformada' => 6,
        ]);
        ControlDeCalidad::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'parte_diario_id' => $parte->id,
            'cantidad_aprobada' => 4,
            'cantidad_rechazada' => 2,
        ]);

        // Act
        $detalle = app(AvancePorEtapasQuery::class)->porOrdenes(
            [$orden->id => $producto->id],
            [$orden->id => 10.0],
        );

        // Assert
        $this->assertSame([
            [
                'etapa_id' => $corte->id,
                'nombre' => $corte->nombre,
                'producida' => 6.0,
                'rechazadas' => 2.0,
                'faltante' => 6.0,
            ],
            [
                'etapa_id' => $lijado->id,
                'nombre' => $lijado->nombre,
                'producida' => 0.0,
                'rechazadas' => 0.0,
                'faltante' => 10.0,
            ],
        ], $detalle[$orden->id]);
    }
}
