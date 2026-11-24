<?php

namespace Tests\Feature\Modules\Predictivo;

use App\Modules\Compras\Models\Cotizacion;
use App\Modules\Compras\Models\DesempenoProveedor;
use App\Modules\Compras\Models\LineaCotizacion;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Inventario\Enums\EstadoArticulo;
use App\Modules\Inventario\Enums\OrigenArticulo;
use App\Modules\Inventario\Enums\TipoArticulo;
use App\Modules\Inventario\Enums\TipoMovimientoStock;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Models\MovimientoDeStock;
use App\Modules\Predictivo\Services\EvaluarRiesgoSuministroService;
use App\Modules\Predictivo\Services\MLEngine;
use App\Modules\Usuarios\Models\ParametroGlobal;
use Database\Seeders\ParametroGlobalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakeMLEngine;
use Tests\TestCase;

class EvaluarRiesgoSuministroServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ParametroGlobalSeeder::class);
    }

    /**
     * @param  array<string, mixed>  $resultado
     */
    private function fake(array $resultado = []): FakeMLEngine
    {
        $fake = new FakeMLEngine($resultado);
        $this->app->instance(MLEngine::class, $fake);

        return $fake;
    }

    private function servicio(): EvaluarRiesgoSuministroService
    {
        return app(EvaluarRiesgoSuministroService::class);
    }

    private function insumoConHistorial(): Articulo
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

        $proveedor = Proveedor::factory()->create();
        DesempenoProveedor::factory()->create([
            'proveedor_id' => $proveedor->id,
            'tiempo_entrega_real' => 5,
            'fecha_registro' => now()->subDays(3)->toDateString(),
        ]);

        $cotizacion = Cotizacion::factory()->create([
            'proveedor_id' => $proveedor->id,
            'fecha' => now()->toDateString(),
        ]);

        LineaCotizacion::factory()->create([
            'cotizacion_id' => $cotizacion->id,
            'articulo_id' => $insumo->id,
        ]);

        return $insumo;
    }

    public function test_it_builds_the_engine_payload_from_the_owner_modules(): void
    {
        $insumo = $this->insumoConHistorial();

        $fake = $this->fake([
            'criticidad' => [[
                'articulo_id' => $insumo->id,
                'disponible' => 100.0,
                'riesgo' => 'medio',
                'motivo' => 'quiebre_antes_de_reposicion',
            ]],
        ]);

        $resultado = $this->servicio()->evaluar('2026-11-24');

        $datos = $fake->llamadas[0]['datos'];

        $this->assertSame('evaluar_riesgo', $fake->llamadas[0]['metodo']);
        $this->assertSame('2026-11-24', $datos['fecha_base']);
        $this->assertSame(7, $datos['dias_aviso']);
        $this->assertCount(1, $datos['insumos']);
        $this->assertSame($insumo->id, $datos['insumos'][0]['articulo_id']);
        $this->assertSame(10.0, $datos['insumos'][0]['consumo_diario']);
        $this->assertSame(5.0, $datos['insumos'][0]['lead_time_dias']);

        $this->assertSame('Tabla de pino', $resultado['criticidad'][0]['descripcion']);
        $this->assertSame(1, $resultado['evaluados']);
    }

    public function test_the_warning_window_comes_from_the_global_parameter(): void
    {
        $this->insumoConHistorial();
        ParametroGlobal::query()
            ->where('nombre', 'predictivo_dias_aviso_riesgo')
            ->update(['valor' => '3']);

        $fake = $this->fake();

        $this->servicio()->evaluar('2026-11-24');

        $this->assertSame(3, $fake->llamadas[0]['datos']['dias_aviso']);
    }

    public function test_an_input_without_history_is_sent_with_zero_lead_time(): void
    {
        Articulo::factory()->create([
            'tipo' => TipoArticulo::MateriaPrima->value,
            'estado' => EstadoArticulo::Activo->value,
            'stock_fisico' => 10,
            'stock_minimo' => 20,
        ]);

        $fake = $this->fake();

        $this->servicio()->evaluar('2026-11-24');

        $this->assertSame(0.0, $fake->llamadas[0]['datos']['insumos'][0]['lead_time_dias']);
        $this->assertSame(0.0, $fake->llamadas[0]['datos']['insumos'][0]['consumo_diario']);
    }
}
