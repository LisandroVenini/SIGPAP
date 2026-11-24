<?php

namespace Tests\Feature\Modules\Predictivo;

use App\Modules\Inventario\Enums\EstadoArticulo;
use App\Modules\Inventario\Enums\OrigenArticulo;
use App\Modules\Inventario\Enums\TipoArticulo;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Predictivo\Models\EscenarioDeSimulacion;
use App\Modules\Predictivo\Services\SimularEscenarioService;
use App\Modules\Produccion\Models\AsignacionDeEtapa;
use App\Modules\Produccion\Models\Etapa;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SimularEscenarioServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(CarbonImmutable::parse('2026-11-02 08:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function servicio(): SimularEscenarioService
    {
        return app(SimularEscenarioService::class);
    }

    private function productoConRuta(float $tiempoPorUnidad = 480): Articulo
    {
        $producto = Articulo::factory()->create([
            'descripcion' => 'Mesa de pino',
            'tipo' => TipoArticulo::ProductoTerminado->value,
            'origen' => OrigenArticulo::Fabricado->value,
            'estado' => EstadoArticulo::Activo->value,
        ]);

        $etapa = Etapa::factory()->create(['nombre' => 'Corte']);
        AsignacionDeEtapa::factory()->create([
            'articulo_id' => $producto->id,
            'etapa_id' => $etapa->id,
            'tiempo_por_unidad' => $tiempoPorUnidad,
            'orden' => 1,
        ]);

        return $producto;
    }

    private function ordenEnCola(Articulo $producto): OrdenDeProduccion
    {
        return OrdenDeProduccion::factory()->create([
            'articulo_id' => $producto->id,
            'estado' => 'Pendiente',
            'cantidad_requerida' => 1,
            'fecha_entrega' => now()->addDays(30)->toDateString(),
            'prioridad_actual' => 1,
        ]);
    }

    public function test_it_inserts_the_order_at_the_front_and_reports_the_delays(): void
    {
        $producto = $this->productoConRuta();
        $existente = $this->ordenEnCola($producto);

        $resultado = $this->servicio()->simular($producto->id, 1, '2026-11-05');

        $this->assertTrue($resultado['viable']);
        $this->assertSame('2026-11-02', $resultado['pedido']['fecha_fin_proyectada']);
        $this->assertCount(1, $resultado['retrasos']);
        $this->assertSame($existente->id, $resultado['retrasos'][0]['orden_id']);
        $this->assertSame(1, $resultado['retrasos'][0]['dias_retraso']);
        $this->assertSame(1.0, $resultado['impacto_proyectado']);

        $escenario = EscenarioDeSimulacion::query()->findOrFail($resultado['escenario_id']);
        $this->assertSame(1, $escenario->prioridad);
        $this->assertSame(1.0, (float) $escenario->impacto_proyectado);
        $this->assertSame('2026-11-05', $escenario->fecha_limite->toDateString());
    }

    public function test_it_does_not_modify_the_real_schedule(): void
    {
        $producto = $this->productoConRuta();
        $existente = $this->ordenEnCola($producto);

        $this->servicio()->simular($producto->id, 1, '2026-11-05');

        $existente->refresh();
        $this->assertSame('Pendiente', $existente->estado);
        $this->assertSame('2026-12-02', $existente->fecha_entrega->toDateString());
        $this->assertSame(1, OrdenDeProduccion::query()->count());
    }

    public function test_it_flags_an_inviable_scenario(): void
    {
        $producto = $this->productoConRuta();

        // Two units need two working days: it cannot finish on the same day.
        $resultado = $this->servicio()->simular($producto->id, 2, '2026-11-02');

        $this->assertFalse($resultado['viable']);
        $this->assertSame('no_llega_a_la_fecha_limite', $resultado['motivo']);
        $this->assertSame('2026-11-03', $resultado['pedido']['fecha_fin_proyectada']);
    }

    public function test_an_article_without_route_is_not_persisted(): void
    {
        $producto = Articulo::factory()->create([
            'tipo' => TipoArticulo::ProductoTerminado->value,
            'origen' => OrigenArticulo::Fabricado->value,
            'estado' => EstadoArticulo::Activo->value,
        ]);

        $resultado = $this->servicio()->simular($producto->id, 1, '2026-11-05');

        $this->assertNull($resultado['escenario_id']);
        $this->assertSame('articulo_sin_ruta', $resultado['motivo']);
        $this->assertSame(0, EscenarioDeSimulacion::query()->count());
    }
}
