<?php

namespace Tests\Feature\Modules\Inventario;

use App\Modules\Compras\Models\Cotizacion;
use App\Modules\Compras\Models\DesempenoProveedor;
use App\Modules\Compras\Models\LineaCotizacion;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Models\SolicitudDeCotizacion;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Models\Reserva;
use App\Modules\Inventario\Services\EvaluarStockMinimoService;
use App\Modules\Inventario\Services\GestionAjustesService;
use App\Modules\Inventario\Services\GestionReservasService;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use App\Shared\Alertas\Models\Alerta;
use Carbon\CarbonImmutable;
use Database\Seeders\ParametroGlobalSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReglaDeAlertaSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EvaluarStockMinimoServiceTest extends TestCase
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

    private function servicio(): EvaluarStockMinimoService
    {
        return app(EvaluarStockMinimoService::class);
    }

    private function articulo(float $stock = 100, float $minimo = 20, float $maximo = 300): Articulo
    {
        return Articulo::factory()->create([
            'descripcion' => 'Tabla de pino',
            'tipo' => 'Materia Prima',
            'estado' => 'Activo',
            'stock_fisico' => $stock,
            'stock_minimo' => $minimo,
            'stock_maximo' => $maximo,
        ]);
    }

    private function reservar(Articulo $articulo, float $cantidad): void
    {
        Reserva::factory()->create([
            'articulo_id' => $articulo->id,
            'cantidad' => $cantidad,
            'estado' => 'Vigente',
        ]);
    }

    public function test_it_creates_the_alert_and_the_draft_when_available_stock_crosses_the_minimum(): void
    {
        $articulo = $this->articulo(stock: 100, minimo: 20, maximo: 300);

        $this->assertFalse($this->servicio()->evaluar($articulo->id));
        $this->assertSame(0, Alerta::query()->count());
        $this->assertSame(0, SolicitudDeCotizacion::query()->count());

        $this->reservar($articulo, 90);

        $this->assertTrue($this->servicio()->evaluar($articulo->id));

        $alerta = Alerta::query()->where('tipo', 'stock_bajo_minimo')->firstOrFail();
        $this->assertSame(10.0, (float) $alerta->contexto['disponible']);

        $solicitud = SolicitudDeCotizacion::query()->with('lineas')->firstOrFail();
        $this->assertSame('Borrador', $solicitud->estado);
        $this->assertSame(290.0, (float) $solicitud->lineas->first()->cantidad_solicitada);
    }

    public function test_it_attaches_the_best_suppliers_with_email(): void
    {
        $articulo = $this->articulo(stock: 5, minimo: 20);

        foreach (['Aceros del Sur', 'Maderas del Norte'] as $nombre) {
            $proveedor = Proveedor::factory()->create([
                'razon_social' => $nombre,
                'email' => 'ventas@'.strtolower(str_replace(' ', '', $nombre)).'.test',
                'estado' => 'Activo',
            ]);

            DesempenoProveedor::factory()->create([
                'proveedor_id' => $proveedor->id,
                'tiempo_entrega_real' => 5,
                'fecha_registro' => now()->subDays(3)->toDateString(),
            ]);

            $cotizacion = Cotizacion::factory()->create([
                'proveedor_id' => $proveedor->id,
                'fecha' => now()->subDays(5)->toDateString(),
            ]);

            LineaCotizacion::factory()->create([
                'cotizacion_id' => $cotizacion->id,
                'articulo_id' => $articulo->id,
            ]);
        }

        $this->servicio()->evaluar($articulo->id);

        $solicitud = SolicitudDeCotizacion::query()
            ->whereHas('lineas', fn ($query) => $query->where('articulo_id', $articulo->id))
            ->firstOrFail();

        $this->assertSame(2, $solicitud->proveedores()->count());
    }

    public function test_it_resolves_the_alert_when_stock_recovers(): void
    {
        $articulo = $this->articulo(stock: 10, minimo: 20);

        $this->servicio()->evaluar($articulo->id);
        $this->assertNotNull(Alerta::query()->where('tipo', 'stock_bajo_minimo')->firstOrFail());

        $articulo->update(['stock_fisico' => 100]);
        $this->servicio()->evaluar($articulo->id);

        $this->assertNotNull(Alerta::query()->where('tipo', 'stock_bajo_minimo')->firstOrFail()->resuelta_en);
    }

    public function test_it_does_not_duplicate_the_draft(): void
    {
        $articulo = $this->articulo(stock: 5, minimo: 20);

        $this->servicio()->evaluar($articulo->id);
        $this->servicio()->evaluar($articulo->id);

        $this->assertSame(1, SolicitudDeCotizacion::query()->count());
    }

    public function test_a_reservation_that_crosses_the_minimum_creates_the_draft(): void
    {
        $articulo = $this->articulo(stock: 30, minimo: 20);
        $orden = OrdenDeProduccion::factory()->create(['articulo_id' => $articulo->id]);

        app(GestionReservasService::class)->reservar($orden->id, [$articulo->id => 15]);

        $this->assertSame(1, SolicitudDeCotizacion::query()->count());
        $this->assertSame(1, Alerta::query()->where('tipo', 'stock_bajo_minimo')->count());
    }

    public function test_a_liberation_that_recovers_the_minimum_closes_the_alert(): void
    {
        $articulo = $this->articulo(stock: 30, minimo: 20);
        $orden = OrdenDeProduccion::factory()->create(['articulo_id' => $articulo->id]);
        $reservas = app(GestionReservasService::class);

        $reservas->reservar($orden->id, [$articulo->id => 15]);
        $this->assertSame(1, Alerta::query()->where('tipo', 'stock_bajo_minimo')->count());

        $reservas->liberar($orden->id);

        $this->assertNotNull(Alerta::query()->where('tipo', 'stock_bajo_minimo')->firstOrFail()->resuelta_en);
    }

    public function test_an_adjustment_below_the_minimum_creates_the_draft(): void
    {
        $articulo = $this->articulo(stock: 100, minimo: 20);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole(Rol::EncargadoStock->value);

        app(GestionAjustesService::class)->ajustar($usuario, $articulo, 10, 'Merma');

        $this->assertSame(1, SolicitudDeCotizacion::query()->count());
        $this->assertSame(1, Alerta::query()->where('tipo', 'stock_bajo_minimo')->count());
    }
}
