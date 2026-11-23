<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Services\GestionArticulosService;
use App\Modules\Predictivo\Services\MLEngine;
use App\Modules\Predictivo\Services\PrediccionDeTiemposService;
use App\Modules\Produccion\Jobs\RecalcularTiempoProduccionJob;
use App\Modules\Produccion\Models\AsignacionDeEtapa;
use App\Modules\Produccion\Models\Etapa;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Models\ParteDiario;
use App\Shared\Alertas\Models\Alerta;
use App\Shared\Alertas\Services\AlertaService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReglaDeAlertaSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakeMLEngine;
use Tests\TestCase;

class RecalcularTiempoProduccionJobTest extends TestCase
{
    use RefreshDatabase;

    private function reglas(): void
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class, ReglaDeAlertaSeeder::class]);
    }

    /**
     * @param  array<string, mixed>  $resultado
     */
    private function fake(array $resultado): void
    {
        $this->app->instance(MLEngine::class, new FakeMLEngine($resultado));
    }

    /**
     * @return array{OrdenDeProduccion, Etapa}
     */
    private function ordenConParte(string $estado = 'En Curso', string $fechaEntrega = '2026-11-15'): array
    {
        $producto = Articulo::factory()->create(['descripcion' => 'Mesa de pino']);
        $etapa = Etapa::factory()->create(['nombre' => 'Corte']);

        AsignacionDeEtapa::factory()->create([
            'articulo_id' => $producto->id,
            'etapa_id' => $etapa->id,
            'tiempo_por_unidad' => 10,
            'orden' => 1,
        ]);

        $orden = OrdenDeProduccion::factory()->create([
            'articulo_id' => $producto->id,
            'estado' => $estado,
            'cantidad_requerida' => 96,
            'fecha_entrega' => $fechaEntrega,
        ]);

        ParteDiario::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $etapa->id,
            'maquinaria_id' => null,
            'fecha' => '2026-11-10',
            'cantidad_conformada' => 48,
        ]);

        return [$orden, $etapa];
    }

    private function ejecutar(OrdenDeProduccion $orden, Etapa $etapa): void
    {
        (new RecalcularTiempoProduccionJob($orden->id, $etapa->id, '2026-11-10'))->handle(
            app(PrediccionDeTiemposService::class),
            app(AlertaService::class),
            app(GestionArticulosService::class),
        );
    }

    public function test_it_emits_the_bottleneck_alert_when_the_projection_exceeds_the_deadline(): void
    {
        $this->reglas();
        [$orden, $etapa] = $this->ordenConParte();

        // 48 pending at 10% of the standard rate: 10 days from 2026-11-10.
        $this->fake(['valor' => 0.1, 'desviacion' => -0.9, 'dias_proyectados' => 10.0]);

        $this->ejecutar($orden, $etapa);

        $alerta = Alerta::query()->where('tipo', 'cuello_de_botella_predictivo')->firstOrFail();

        $this->assertSame($orden->id, $alerta->orden_de_produccion_id);
        $this->assertNull($alerta->resuelta_en);
        $this->assertStringContainsString('se proyecta para el 2026-11-20', $alerta->descripcion);
        $this->assertStringContainsString('5 días después de la entrega', $alerta->descripcion);
    }

    public function test_it_closes_the_alert_when_the_order_stops_exceeding_its_deadline(): void
    {
        $this->reglas();
        [$orden, $etapa] = $this->ordenConParte();

        $this->fake(['valor' => 0.1, 'desviacion' => -0.9, 'dias_proyectados' => 10.0]);
        $this->ejecutar($orden, $etapa);

        $this->fake(['valor' => 1.0, 'desviacion' => 0.0, 'dias_proyectados' => 1.0]);
        $this->ejecutar($orden, $etapa);

        $alerta = Alerta::query()->where('tipo', 'cuello_de_botella_predictivo')->firstOrFail();

        $this->assertNotNull($alerta->resuelta_en);
    }

    public function test_it_refreshes_the_open_alert_instead_of_duplicating_it(): void
    {
        $this->reglas();
        [$orden, $etapa] = $this->ordenConParte();

        $this->fake(['valor' => 0.1, 'desviacion' => -0.9, 'dias_proyectados' => 10.0]);
        $this->ejecutar($orden, $etapa);
        $this->ejecutar($orden, $etapa);

        $this->assertSame(1, Alerta::query()->where('tipo', 'cuello_de_botella_predictivo')->count());
    }

    public function test_it_stays_quiet_when_the_order_left_the_schedule(): void
    {
        $this->reglas();
        [$orden, $etapa] = $this->ordenConParte(estado: 'Cerrada');

        $this->fake(['valor' => 0.1, 'desviacion' => -0.9, 'dias_proyectados' => 10.0]);

        $this->ejecutar($orden, $etapa);

        $this->assertSame(0, Alerta::query()->where('tipo', 'cuello_de_botella_predictivo')->count());
    }
}
