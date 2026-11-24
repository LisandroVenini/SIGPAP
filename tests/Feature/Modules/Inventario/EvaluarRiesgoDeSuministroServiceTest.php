<?php

namespace Tests\Feature\Modules\Inventario;

use App\Modules\Compras\Models\SolicitudDeCotizacion;
use App\Modules\Inventario\Enums\EstadoArticulo;
use App\Modules\Inventario\Enums\OrigenArticulo;
use App\Modules\Inventario\Enums\TipoArticulo;
use App\Modules\Inventario\Enums\TipoMovimientoStock;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Models\MovimientoDeStock;
use App\Modules\Inventario\Services\EvaluarRiesgoDeSuministroService;
use App\Modules\Predictivo\Services\MLEngine;
use App\Shared\Alertas\Models\Alerta;
use Database\Seeders\ParametroGlobalSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReglaDeAlertaSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakeMLEngine;
use Tests\TestCase;

class EvaluarRiesgoDeSuministroServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            PermissionSeeder::class,
            ParametroGlobalSeeder::class,
            ReglaDeAlertaSeeder::class,
        ]);
    }

    private function servicio(): EvaluarRiesgoDeSuministroService
    {
        return app(EvaluarRiesgoDeSuministroService::class);
    }

    private function fake(array $criticidad): FakeMLEngine
    {
        $fake = new FakeMLEngine(['criticidad' => $criticidad]);
        $this->app->instance(MLEngine::class, $fake);

        return $fake;
    }

    private function insumo(): Articulo
    {
        $insumo = Articulo::factory()->create([
            'descripcion' => 'Tabla de pino',
            'tipo' => TipoArticulo::MateriaPrima->value,
            'origen' => OrigenArticulo::Tercero->value,
            'estado' => EstadoArticulo::Activo->value,
            'stock_fisico' => 100,
            'stock_minimo' => 20,
            'stock_maximo' => 300,
        ]);

        MovimientoDeStock::query()->create([
            'articulo_id' => $insumo->id,
            'tipo' => TipoMovimientoStock::Consumo->value,
            'cantidad' => 900,
            'fecha' => now()->subDays(10)->toDateString(),
        ]);

        return $insumo;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function criticidad(Articulo $insumo): array
    {
        return [[
            'articulo_id' => $insumo->id,
            'disponible' => 100.0,
            'consumo_diario' => 10.0,
            'dias_hasta_quiebre' => 10.0,
            'fecha_quiebre' => '2026-12-04',
            'lead_time_dias' => 5.0,
            'fecha_limite_pedido' => '2026-11-29',
            'riesgo' => 'medio',
            'motivo' => 'quiebre_antes_de_reposicion',
            'cantidad_sugerida' => 200.0,
        ]];
    }

    public function test_it_emits_the_stock_break_alert_and_creates_a_draft(): void
    {
        $insumo = $this->insumo();
        $this->fake($this->criticidad($insumo));

        $resultado = $this->servicio()->evaluar('2026-11-24');

        $this->assertSame(1, $resultado['criticos']);
        $this->assertSame(1, $resultado['alertas']);
        $this->assertSame(1, $resultado['borradores']);

        $alerta = Alerta::query()->where('tipo', 'quiebre_de_stock')->firstOrFail();
        $this->assertSame($insumo->id, $alerta->articulo_id);
        $this->assertStringContainsString('se agota en 10', $alerta->descripcion);

        $solicitud = SolicitudDeCotizacion::query()->with('lineas')->firstOrFail();
        $this->assertSame('Borrador', $solicitud->estado);
        $this->assertSame(200.0, (float) $solicitud->lineas->first()->cantidad_solicitada);
    }

    public function test_it_closes_the_alert_when_the_input_recovers(): void
    {
        $insumo = $this->insumo();
        $this->fake($this->criticidad($insumo));
        $this->servicio()->evaluar('2026-11-24');

        $this->fake([]);
        $resultado = $this->servicio()->evaluar('2026-11-25');

        $this->assertSame(0, $resultado['criticos']);
        $this->assertNotNull(Alerta::query()->where('tipo', 'quiebre_de_stock')->firstOrFail()->resuelta_en);
    }

    public function test_it_does_not_duplicate_the_draft_on_a_second_run(): void
    {
        $insumo = $this->insumo();
        $this->fake($this->criticidad($insumo));

        $this->servicio()->evaluar('2026-11-24');
        $segundo = $this->servicio()->evaluar('2026-11-24');

        $this->assertSame(0, $segundo['borradores']);
        $this->assertSame(1, SolicitudDeCotizacion::query()->count());
    }

    public function test_it_does_not_touch_anything_when_there_is_no_risk(): void
    {
        $this->insumo();
        $this->fake([]);

        $resultado = $this->servicio()->evaluar('2026-11-24');

        $this->assertSame(['evaluados' => 1, 'criticos' => 0, 'alertas' => 0, 'borradores' => 0], $resultado);
        $this->assertSame(0, Alerta::query()->count());
        $this->assertSame(0, SolicitudDeCotizacion::query()->count());
    }
}
