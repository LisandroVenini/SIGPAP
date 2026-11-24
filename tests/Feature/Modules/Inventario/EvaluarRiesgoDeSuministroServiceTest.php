<?php

namespace Tests\Feature\Modules\Inventario;

use App\Modules\Compras\Mail\SolicitudDeCotizacionMail;
use App\Modules\Compras\Models\Cotizacion;
use App\Modules\Compras\Models\DesempenoProveedor;
use App\Modules\Compras\Models\LineaCotizacion;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Models\SolicitudDeCotizacion;
use App\Modules\Inventario\Enums\EstadoArticulo;
use App\Modules\Inventario\Enums\OrigenArticulo;
use App\Modules\Inventario\Enums\TipoArticulo;
use App\Modules\Inventario\Enums\TipoMovimientoStock;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Models\MovimientoDeStock;
use App\Modules\Inventario\Services\EvaluarRiesgoDeSuministroService;
use App\Modules\Predictivo\Services\MLEngine;
use App\Modules\Usuarios\Models\ParametroGlobal;
use App\Shared\Alertas\Models\Alerta;
use Database\Seeders\ParametroGlobalSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReglaDeAlertaSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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

    private function solicitudDelArticulo(Articulo $insumo): SolicitudDeCotizacion
    {
        return SolicitudDeCotizacion::query()
            ->whereHas('lineas', fn ($query) => $query->where('articulo_id', $insumo->id))
            ->orderByDesc('id')
            ->firstOrFail();
    }

    private function insumoConProveedor(): Articulo
    {
        $insumo = $this->insumo();

        $proveedor = Proveedor::factory()->create(['email' => 'ventas@proveedor.test']);
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

    /**
     * @return array<int, array<string, mixed>>
     */
    private function criticidad(Articulo $insumo, string $riesgo = 'medio'): array
    {
        return [[
            'articulo_id' => $insumo->id,
            'disponible' => 100.0,
            'consumo_diario' => 10.0,
            'dias_hasta_quiebre' => 10.0,
            'fecha_quiebre' => '2026-12-04',
            'lead_time_dias' => 5.0,
            'fecha_limite_pedido' => '2026-11-29',
            'riesgo' => $riesgo,
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

        $this->assertSame(['evaluados' => 1, 'criticos' => 0, 'alertas' => 0, 'borradores' => 0, 'licitaciones' => 0], $resultado);
        $this->assertSame(0, Alerta::query()->count());
        $this->assertSame(0, SolicitudDeCotizacion::query()->count());
    }

    public function test_it_fires_the_licitation_when_the_deadline_arrived_and_the_feature_is_enabled(): void
    {
        Mail::fake();
        $insumo = $this->insumoConProveedor();
        $this->fake($this->criticidad($insumo, 'alto'));
        ParametroGlobal::query()->where('nombre', 'predictivo_licitacion_automatica')->update(['valor' => '1']);

        $resultado = $this->servicio()->evaluar('2026-11-24');

        $this->assertSame(1, $resultado['licitaciones']);

        $solicitud = $this->solicitudDelArticulo($insumo);
        $this->assertSame('Enviada', $solicitud->estado);
        $this->assertSame(1, $solicitud->proveedores()->count());
        $this->assertNotNull($solicitud->proveedores()->firstOrFail()->pivot->token);

        Mail::assertSent(SolicitudDeCotizacionMail::class);
        $this->assertDatabaseHas('eventos_de_auditoria', ['accion' => 'disparar_licitacion_automatica']);
    }

    public function test_it_does_not_fire_while_the_feature_is_disabled(): void
    {
        Mail::fake();
        $insumo = $this->insumoConProveedor();
        $this->fake($this->criticidad($insumo, 'alto'));

        $resultado = $this->servicio()->evaluar('2026-11-24');

        $this->assertSame(0, $resultado['licitaciones']);
        $this->assertSame('Borrador', $this->solicitudDelArticulo($insumo)->estado);
        Mail::assertNothingSent();
    }

    public function test_it_does_not_fire_a_medium_risk(): void
    {
        Mail::fake();
        $insumo = $this->insumoConProveedor();
        $this->fake($this->criticidad($insumo, 'medio'));
        ParametroGlobal::query()->where('nombre', 'predictivo_licitacion_automatica')->update(['valor' => '1']);

        $resultado = $this->servicio()->evaluar('2026-11-24');

        $this->assertSame(0, $resultado['licitaciones']);
        $this->assertSame('Borrador', $this->solicitudDelArticulo($insumo)->estado);
    }

    public function test_it_alerts_when_the_licitation_cannot_be_fired(): void
    {
        Mail::fake();
        $insumo = $this->insumo();
        $this->fake($this->criticidad($insumo, 'alto'));
        ParametroGlobal::query()->where('nombre', 'predictivo_licitacion_automatica')->update(['valor' => '1']);

        $resultado = $this->servicio()->evaluar('2026-11-24');

        $this->assertSame(0, $resultado['licitaciones']);
        $this->assertSame('Borrador', $this->solicitudDelArticulo($insumo)->estado);
        $this->assertDatabaseHas('alertas', ['tipo' => 'licitacion_no_disparada', 'articulo_id' => $insumo->id]);
    }
}
