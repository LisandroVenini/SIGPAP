<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Inventario\Enums\EstadoArticulo;
use App\Modules\Inventario\Enums\OrigenArticulo;
use App\Modules\Inventario\Enums\TipoArticulo;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Produccion\Models\AsignacionDeEtapa;
use App\Modules\Produccion\Models\Etapa;
use App\Modules\Produccion\Models\LineaBaseDeCronograma;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Services\CapturarLineaBaseService;
use App\Modules\Produccion\Services\GestionCronogramaService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CapturarLineaBaseServiceTest extends TestCase
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

    private function ordenConRuta(): OrdenDeProduccion
    {
        $producto = Articulo::factory()->create([
            'tipo' => TipoArticulo::ProductoTerminado->value,
            'origen' => OrigenArticulo::Fabricado->value,
            'estado' => EstadoArticulo::Activo->value,
        ]);

        foreach ([60, 30] as $indice => $tiempo) {
            $etapa = Etapa::factory()->create();
            AsignacionDeEtapa::factory()->create([
                'articulo_id' => $producto->id,
                'etapa_id' => $etapa->id,
                'tiempo_por_unidad' => $tiempo,
                'orden' => $indice + 1,
            ]);
        }

        return OrdenDeProduccion::factory()->create([
            'articulo_id' => $producto->id,
            'estado' => 'Pendiente',
            'cantidad_requerida' => 8,
            'fecha_entrega' => now()->addDays(30)->toDateString(),
        ]);
    }

    public function test_it_captures_one_baseline_row_per_stage(): void
    {
        $orden = $this->ordenConRuta();

        $capturadas = app(CapturarLineaBaseService::class)->capturar($orden->id);

        $this->assertSame(2, $capturadas);
        $this->assertSame(
            2,
            LineaBaseDeCronograma::query()->where('orden_de_produccion_id', $orden->id)->count(),
        );

        $fila = collect(app(GestionCronogramaService::class)->proyectar()['filas'])
            ->firstWhere('orden_id', $orden->id);

        $this->assertNotNull($fila['etapas'][0]['plan']);
        $this->assertSame('2026-11-02 08:00:00', $fila['etapas'][0]['plan']['inicio']);
    }

    public function test_it_never_overwrites_an_existing_baseline(): void
    {
        $orden = $this->ordenConRuta();
        app(CapturarLineaBaseService::class)->capturar($orden->id);

        $original = LineaBaseDeCronograma::query()->orderBy('etapa_id')->get()->toArray();

        $orden->update(['fecha_entrega' => now()->addDays(60)->toDateString()]);

        $repetido = app(CapturarLineaBaseService::class)->capturar($orden->id);

        $this->assertSame(0, $repetido);
        $this->assertSame(
            $original,
            LineaBaseDeCronograma::query()->orderBy('etapa_id')->get()->toArray(),
        );
    }

    public function test_an_order_without_route_does_not_create_a_baseline(): void
    {
        $producto = Articulo::factory()->create([
            'tipo' => TipoArticulo::ProductoTerminado->value,
            'origen' => OrigenArticulo::Fabricado->value,
            'estado' => EstadoArticulo::Activo->value,
        ]);

        $orden = OrdenDeProduccion::factory()->create([
            'articulo_id' => $producto->id,
            'estado' => 'Pendiente',
        ]);

        $capturadas = app(CapturarLineaBaseService::class)->capturar($orden->id);

        $this->assertSame(0, $capturadas);
        $this->assertSame(0, LineaBaseDeCronograma::query()->count());
    }
}
