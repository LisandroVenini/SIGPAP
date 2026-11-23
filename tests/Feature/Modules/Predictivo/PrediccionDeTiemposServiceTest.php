<?php

namespace Tests\Feature\Modules\Predictivo;

use App\Modules\Inventario\Models\Articulo;
use App\Modules\Predictivo\Models\TasaDeRendimiento;
use App\Modules\Predictivo\Services\MLEngine;
use App\Modules\Predictivo\Services\MLEngineException;
use App\Modules\Predictivo\Services\PrediccionDeTiemposException;
use App\Modules\Predictivo\Services\PrediccionDeTiemposService;
use App\Modules\Produccion\Models\AsignacionDeEtapa;
use App\Modules\Produccion\Models\ControlDeCalidad;
use App\Modules\Produccion\Models\Etapa;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Models\ParteDiario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakeMLEngine;
use Tests\TestCase;

class PrediccionDeTiemposServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $resultado
     */
    private function fake(array $resultado = [], ?MLEngineException $error = null): FakeMLEngine
    {
        $fake = new FakeMLEngine($resultado, $error);
        $this->app->instance(MLEngine::class, $fake);

        return $fake;
    }

    private function servicio(): PrediccionDeTiemposService
    {
        return app(PrediccionDeTiemposService::class);
    }

    /**
     * @return array{Articulo, Etapa, Etapa, OrdenDeProduccion}
     */
    private function contexto(
        float $cantidadRequerida = 96,
        string $fechaEntrega = '2026-11-20',
        float $tiempoPrimera = 10,
        float $tiempoSegunda = 20,
        string $estado = 'En Curso',
    ): array {
        $producto = Articulo::factory()->create();
        $primera = Etapa::factory()->create(['nombre' => 'Corte']);
        $segunda = Etapa::factory()->create(['nombre' => 'Ensamble']);

        AsignacionDeEtapa::factory()->create([
            'articulo_id' => $producto->id,
            'etapa_id' => $primera->id,
            'tiempo_por_unidad' => $tiempoPrimera,
            'orden' => 1,
        ]);
        AsignacionDeEtapa::factory()->create([
            'articulo_id' => $producto->id,
            'etapa_id' => $segunda->id,
            'tiempo_por_unidad' => $tiempoSegunda,
            'orden' => 2,
        ]);

        $orden = OrdenDeProduccion::factory()->create([
            'articulo_id' => $producto->id,
            'estado' => $estado,
            'cantidad_requerida' => $cantidadRequerida,
            'fecha_entrega' => $fechaEntrega,
        ]);

        return [$producto, $primera, $segunda, $orden];
    }

    private function parte(OrdenDeProduccion $orden, Etapa $etapa, string $fecha, float $cantidad): ParteDiario
    {
        return ParteDiario::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $etapa->id,
            'maquinaria_id' => null,
            'fecha' => $fecha,
            'cantidad_conformada' => $cantidad,
        ]);
    }

    public function test_it_persists_the_rate_and_projects_the_finish_date(): void
    {
        [, $primera, , $orden] = $this->contexto();
        $this->parte($orden, $primera, '2026-11-10', 48);

        $fake = $this->fake(['valor' => 1.0, 'desviacion' => 0.0, 'dias_proyectados' => 1.0]);

        $resultado = $this->servicio()->recalcularTiempoProduccion($orden->id, $primera->id, '2026-11-10');

        // Current stage: 48 pending at the projected rate (1 day). Next stage:
        // 96 units at 24 units per day (4 days). Total: 5 days from 2026-11-10.
        $this->assertSame('2026-11-15', $resultado['fecha_proyectada']);
        $this->assertSame('2026-11-20', $resultado['fecha_entrega']);
        $this->assertFalse($resultado['excede_plazo']);
        $this->assertSame(0, $resultado['dias_de_retraso']);
        $this->assertSame('Corte', $resultado['etapa']);

        $tasa = TasaDeRendimiento::query()->firstOrFail();
        $this->assertSame($primera->id, $tasa->etapa_id);
        $this->assertSame(1.0, (float) $tasa->valor);
        $this->assertSame(0.0, (float) $tasa->desviacion);
        $this->assertSame('2026-11-10', $tasa->fecha_calculo->toDateString());

        $this->assertSame(48.0, $fake->llamadas[0]['datos']['cantidad_pendiente']);
        $this->assertSame(
            [['fecha' => '2026-11-10', 'cantidad_conformada' => 48.0]],
            $fake->llamadas[0]['datos']['muestras'],
        );
    }

    public function test_the_pending_quantity_adds_the_rejected_units(): void
    {
        [, $primera, , $orden] = $this->contexto();
        $parte = $this->parte($orden, $primera, '2026-11-10', 48);

        ControlDeCalidad::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'parte_diario_id' => $parte->id,
            'cantidad_rechazada' => 10,
        ]);

        $fake = $this->fake();

        $this->servicio()->recalcularTiempoProduccion($orden->id, $primera->id, '2026-11-10');

        // 96 required + 10 rejected - 48 conformed = 58 pending.
        $this->assertSame(58.0, $fake->llamadas[0]['datos']['cantidad_pendiente']);
    }

    public function test_it_flags_the_deadline_when_the_projection_exceeds_it(): void
    {
        [, $primera, , $orden] = $this->contexto(fechaEntrega: '2026-11-15');
        $this->parte($orden, $primera, '2026-11-10', 48);

        $this->fake(['valor' => 0.5, 'desviacion' => -0.5, 'dias_proyectados' => 2.0]);

        $resultado = $this->servicio()->recalcularTiempoProduccion($orden->id, $primera->id, '2026-11-10');

        // 2 days of the current stage + 96 / (24 * 0.5) = 8 days of the next one.
        $this->assertSame('2026-11-20', $resultado['fecha_proyectada']);
        $this->assertTrue($resultado['excede_plazo']);
        $this->assertSame(5, $resultado['dias_de_retraso']);
    }

    public function test_it_rejects_an_order_that_left_the_schedule(): void
    {
        [, $primera, , $orden] = $this->contexto(estado: 'Cerrada');

        $this->expectException(PrediccionDeTiemposException::class);

        try {
            $this->servicio()->recalcularTiempoProduccion($orden->id, $primera->id, '2026-11-10');
        } catch (PrediccionDeTiemposException $error) {
            $this->assertSame('orden_no_disponible', $error->codigo);
            $this->assertSame(0, TasaDeRendimiento::query()->count());

            throw $error;
        }
    }

    public function test_it_rejects_a_stage_outside_the_route_sheet(): void
    {
        [, , , $orden] = $this->contexto();
        $ajena = Etapa::factory()->create();

        $this->expectException(PrediccionDeTiemposException::class);

        try {
            $this->servicio()->recalcularTiempoProduccion($orden->id, $ajena->id, '2026-11-10');
        } catch (PrediccionDeTiemposException $error) {
            $this->assertSame('etapa_fuera_de_ruta', $error->codigo);

            throw $error;
        }
    }

    public function test_it_rejects_a_permanent_engine_error_without_persisting_a_rate(): void
    {
        [, $primera, , $orden] = $this->contexto();
        $this->parte($orden, $primera, '2026-11-10', 48);

        $this->fake([], MLEngineException::errorDeDominio('predecir_trr', 'campo_invalido', 'boom'));

        $this->expectException(PrediccionDeTiemposException::class);

        try {
            $this->servicio()->recalcularTiempoProduccion($orden->id, $primera->id, '2026-11-10');
        } catch (PrediccionDeTiemposException $error) {
            $this->assertSame('datos_invalidos', $error->codigo);
            $this->assertSame(0, TasaDeRendimiento::query()->count());

            throw $error;
        }
    }
}
