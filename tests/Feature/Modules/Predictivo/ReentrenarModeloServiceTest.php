<?php

namespace Tests\Feature\Modules\Predictivo;

use App\Modules\Inventario\Enums\EstadoArticulo;
use App\Modules\Inventario\Enums\OrigenArticulo;
use App\Modules\Inventario\Enums\TipoArticulo;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Predictivo\Services\MLEngine;
use App\Modules\Predictivo\Services\ReentrenarModeloService;
use App\Modules\Produccion\Models\AsignacionDeEtapa;
use App\Modules\Produccion\Models\Etapa;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Models\ParteDiario;
use App\Shared\Alertas\Models\Alerta;
use Carbon\CarbonImmutable;
use Database\Seeders\ParametroGlobalSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReglaDeAlertaSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Fakes\FakeMLEngine;
use Tests\TestCase;

class ReentrenarModeloServiceTest extends TestCase
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

        Carbon::setTestNow(CarbonImmutable::parse('2026-11-24 10:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function etapaConPartes(int $cantidadMuestras = 4): Etapa
    {
        $producto = Articulo::factory()->create([
            'tipo' => TipoArticulo::ProductoTerminado->value,
            'origen' => OrigenArticulo::Fabricado->value,
            'estado' => EstadoArticulo::Activo->value,
        ]);

        $etapa = Etapa::factory()->create(['nombre' => 'Corte']);

        AsignacionDeEtapa::factory()->create([
            'articulo_id' => $producto->id,
            'etapa_id' => $etapa->id,
            'tiempo_por_unidad' => 10,
            'orden' => 1,
        ]);

        $orden = OrdenDeProduccion::factory()->create([
            'articulo_id' => $producto->id,
            'estado' => 'Cerrada',
        ]);

        for ($indice = 0; $indice < $cantidadMuestras; $indice++) {
            ParteDiario::factory()->create([
                'orden_de_produccion_id' => $orden->id,
                'etapa_id' => $etapa->id,
                'maquinaria_id' => null,
                'fecha' => now()->subDays($cantidadMuestras - $indice)->toDateString(),
                'cantidad_conformada' => 40,
            ]);
        }

        return $etapa;
    }

    /**
     * @param  array<string, mixed>  $resultado
     */
    private function fake(array $resultado): FakeMLEngine
    {
        $fake = new FakeMLEngine($resultado);
        $this->app->instance(MLEngine::class, $fake);

        return $fake;
    }

    private function servicio(): ReentrenarModeloService
    {
        return app(ReentrenarModeloService::class);
    }

    public function test_it_trains_each_stage_and_records_the_audit_event(): void
    {
        $this->etapaConPartes();

        $fake = $this->fake([
            'metricas' => ['r2' => 0.8, 'mae' => 0.1, 'n_muestras' => 4],
            'metricas_anteriores' => null,
            'delta' => ['r2' => null, 'mae' => null],
            'n_muestras' => 4,
        ]);

        $resultado = $this->servicio()->reentrenar('2026-11-01', '2026-11-30');

        $this->assertSame(1, $resultado['entrenadas']);
        $this->assertSame('entrenar', $fake->llamadas[0]['metodo']);
        $this->assertCount(4, $fake->llamadas[0]['datos']['muestras']);
        $this->assertSame(10.0, $fake->llamadas[0]['datos']['muestras'][0]['tiempo_por_unidad']);
        $this->assertDatabaseHas('eventos_de_auditoria', ['accion' => 'Reentrenamiento del Modelo']);
        $this->assertSame(0, Alerta::query()->count());
    }

    public function test_it_alerts_when_the_metrics_drift(): void
    {
        $this->etapaConPartes();

        $this->fake([
            'metricas' => ['r2' => 0.5, 'mae' => 0.2, 'n_muestras' => 4],
            'metricas_anteriores' => ['r2' => 0.9, 'mae' => 0.1, 'n_muestras' => 4],
            'delta' => ['r2' => -0.4, 'mae' => 0.1],
            'n_muestras' => 4,
        ]);

        $resultado = $this->servicio()->reentrenar('2026-11-01', '2026-11-30');

        $this->assertCount(1, $resultado['drift']);

        $alerta = Alerta::query()->where('tipo', 'drift_detectado')->firstOrFail();
        $this->assertStringContainsString('Corte', $alerta->descripcion);
    }

    public function test_a_small_change_does_not_raise_drift(): void
    {
        $this->etapaConPartes();

        $this->fake([
            'metricas' => ['r2' => 0.78, 'mae' => 0.11, 'n_muestras' => 4],
            'metricas_anteriores' => ['r2' => 0.8, 'mae' => 0.1, 'n_muestras' => 4],
            'delta' => ['r2' => -0.02, 'mae' => 0.01],
            'n_muestras' => 4,
        ]);

        $resultado = $this->servicio()->reentrenar('2026-11-01', '2026-11-30');

        $this->assertSame([], $resultado['drift']);
        $this->assertSame(0, Alerta::query()->count());
    }

    public function test_it_skips_a_stage_with_too_few_samples(): void
    {
        $this->etapaConPartes(2);

        $fake = $this->fake([]);

        $resultado = $this->servicio()->reentrenar('2026-11-01', '2026-11-30');

        $this->assertSame(0, $resultado['entrenadas']);
        $this->assertSame(1, $resultado['omitidas']);
        $this->assertSame([], $fake->llamadas);
    }
}
