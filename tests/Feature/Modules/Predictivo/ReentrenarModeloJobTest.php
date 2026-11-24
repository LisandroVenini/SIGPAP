<?php

namespace Tests\Feature\Modules\Predictivo;

use App\Modules\Inventario\Enums\EstadoArticulo;
use App\Modules\Inventario\Enums\OrigenArticulo;
use App\Modules\Inventario\Enums\TipoArticulo;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Predictivo\Jobs\ReentrenarModeloJob;
use App\Modules\Predictivo\Services\MLEngine;
use App\Modules\Predictivo\Services\MLEngineException;
use App\Modules\Predictivo\Services\ReentrenarModeloService;
use App\Modules\Produccion\Models\AsignacionDeEtapa;
use App\Modules\Produccion\Models\Etapa;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Models\ParteDiario;
use App\Shared\Alertas\Services\AlertaService;
use Carbon\CarbonImmutable;
use Database\Seeders\ParametroGlobalSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReglaDeAlertaSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\Fakes\FakeMLEngine;
use Tests\TestCase;

class ReentrenarModeloJobTest extends TestCase
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

    private function etapaConPartes(): void
    {
        $producto = Articulo::factory()->create([
            'tipo' => TipoArticulo::ProductoTerminado->value,
            'origen' => OrigenArticulo::Fabricado->value,
            'estado' => EstadoArticulo::Activo->value,
        ]);

        $etapa = Etapa::factory()->create();

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

        for ($indice = 0; $indice < 4; $indice++) {
            ParteDiario::factory()->create([
                'orden_de_produccion_id' => $orden->id,
                'etapa_id' => $etapa->id,
                'maquinaria_id' => null,
                'fecha' => now()->subDays(4 - $indice)->toDateString(),
                'cantidad_conformada' => 40,
            ]);
        }
    }

    public function test_a_timeout_alerts_the_administrator(): void
    {
        $this->etapaConPartes();

        $this->app->instance(MLEngine::class, new FakeMLEngine(
            error: MLEngineException::timeout('entrenar', 300),
        ));

        (new ReentrenarModeloJob)->handle(
            app(ReentrenarModeloService::class),
            app(AlertaService::class),
        );

        $this->assertDatabaseHas('alertas', ['tipo' => 'reentrenamiento_fallido']);
    }

    public function test_a_successful_run_records_no_failure_alert(): void
    {
        $this->etapaConPartes();

        $this->app->instance(MLEngine::class, new FakeMLEngine);

        (new ReentrenarModeloJob)->handle(
            app(ReentrenarModeloService::class),
            app(AlertaService::class),
        );

        $this->assertDatabaseMissing('alertas', ['tipo' => 'reentrenamiento_fallido']);
        $this->assertDatabaseHas('eventos_de_auditoria', ['accion' => 'Reentrenamiento del Modelo']);
    }

    public function test_the_command_enqueues_the_job(): void
    {
        Queue::fake();

        $this->artisan('predictivo:reentrenar')->assertSuccessful();

        Queue::assertPushed(ReentrenarModeloJob::class);
    }
}
