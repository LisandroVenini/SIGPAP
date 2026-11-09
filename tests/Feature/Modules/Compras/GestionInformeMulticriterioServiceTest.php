<?php

namespace Tests\Feature\Modules\Compras;

use App\Modules\Compras\Models\Cotizacion;
use App\Modules\Compras\Models\DesempenoProveedor;
use App\Modules\Compras\Models\Incidencia;
use App\Modules\Compras\Models\OrdenDeCompra;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Models\SolicitudDeCotizacion;
use App\Modules\Compras\Services\GestionInformeMulticriterioService;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Usuarios\Models\ParametroGlobal;
use Database\Factories\CotizacionFactory;
use Database\Factories\LineaCotizacionFactory;
use Database\Factories\SolicitudDeCotizacionFactory;
use Database\Factories\SolicitudDeDisponibilidadFactory;
use Database\Seeders\ParametroGlobalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GestionInformeMulticriterioServiceTest extends TestCase
{
    use RefreshDatabase;

    private int $secuencia = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-09 10:00:00'));
    }

    private function service(): GestionInformeMulticriterioService
    {
        return app(GestionInformeMulticriterioService::class);
    }

    private function proveedor(string $razonSocial): Proveedor
    {
        $this->secuencia++;

        return Proveedor::factory()->create([
            'razon_social' => $razonSocial,
            'cuit' => sprintf('30-%08d-1', $this->secuencia),
            'email' => sprintf('proveedor%d@example.test', $this->secuencia),
            'estado' => 'Activo',
        ]);
    }

    /**
     * @param  array<int, string>  $incidencias
     */
    private function entrega(Proveedor $proveedor, array $incidencias = []): void
    {
        $this->secuencia++;

        $solicitud = SolicitudDeDisponibilidadFactory::new()->create([
            'proveedor_id' => $proveedor->id,
        ]);

        $orden = OrdenDeCompra::factory()->create([
            'numero_oc' => sprintf('OC-INF-%04d', $this->secuencia),
            'proveedor_id' => $proveedor->id,
            'solicitud_de_disponibilidad_id' => $solicitud->id,
        ]);

        foreach ($incidencias as $tipo) {
            Incidencia::create([
                'proveedor_id' => $proveedor->id,
                'orden_de_compra_id' => $orden->id,
                'tipo' => $tipo,
                'fecha' => today(),
                'detalle' => 'Detalle de prueba',
            ]);
        }

        DesempenoProveedor::create([
            'proveedor_id' => $proveedor->id,
            'orden_de_compra_id' => $orden->id,
            'tiempo_entrega_real' => 3,
            'fecha_registro' => today(),
        ]);
    }

    private function articulo(string $descripcion = 'Tabla de pino'): Articulo
    {
        return Articulo::factory()->create([
            'descripcion' => $descripcion,
            'tipo' => 'Materia Prima',
            'estado' => 'Activo',
        ]);
    }

    private function solicitud(Articulo $articulo, float $cantidad = 10): SolicitudDeCotizacion
    {
        $solicitud = SolicitudDeCotizacionFactory::new()->create([
            'fecha' => today(),
            'fecha_vencimiento' => today()->addWeek(),
            'estado' => 'Enviada',
        ]);

        $solicitud->lineas()->create([
            'articulo_id' => $articulo->id,
            'cantidad_solicitada' => $cantidad,
        ]);

        return $solicitud;
    }

    private function cotizacion(
        SolicitudDeCotizacion $solicitud,
        Proveedor $proveedor,
        Articulo $articulo,
        float $precioUnitario,
        float $cantidadOfertada,
        int $diasEntrega,
    ): Cotizacion {
        $cotizacion = CotizacionFactory::new()->create([
            'solicitud_de_cotizacion_id' => $solicitud->id,
            'solicitud_de_disponibilidad_id' => null,
            'proveedor_id' => $proveedor->id,
        ]);

        LineaCotizacionFactory::new()->create([
            'cotizacion_id' => $cotizacion->id,
            'articulo_id' => $articulo->id,
            'precio_unitario' => $precioUnitario,
            'cantidad_ofertada' => $cantidadOfertada,
            'fecha_prometida' => today()->addDays($diasEntrega),
        ]);

        return $cotizacion;
    }

    /**
     * @return array{cotizacion: Cotizacion, proveedor: Proveedor}
     */
    private function fixtureDelEjemplo(): array
    {
        $this->seed(ParametroGlobalSeeder::class);

        $articulo = $this->articulo();

        $a = $this->proveedor('Aceros del Sur');
        $b = $this->proveedor('Maderas Norte');
        $c = $this->proveedor('Vidrios Paraná');

        $this->entrega($a);
        $this->entrega($b, ['Retraso']);
        $this->entrega($c, ['Retraso']);

        $solicitud = $this->solicitud($articulo);

        $cotizacionA = $this->cotizacion($solicitud, $a, $articulo, 17.0, 10, 3);
        $cotizacionB = $this->cotizacion($solicitud, $b, $articulo, 8.0, 10, 9);
        $cotizacionC = $this->cotizacion($solicitud, $c, $articulo, 11.0, 10, 5);

        return [
            'a' => ['cotizacion' => $cotizacionA, 'proveedor' => $a],
            'b' => ['cotizacion' => $cotizacionB, 'proveedor' => $b],
            'c' => ['cotizacion' => $cotizacionC, 'proveedor' => $c],
            'solicitud' => $solicitud,
        ];
    }

    public function test_it_normalizes_price_delivery_and_reliability_and_recommends_the_best_quote(): void
    {
        // Arrange
        $fixture = $this->fixtureDelEjemplo();

        // Act
        $informe = $this->service()->paraSolicitud($fixture['solicitud']->id);

        // Assert
        $this->assertSame(['precio' => 0.4, 'entrega' => 0.3, 'confiabilidad' => 0.3], $informe['parametros']['pesos']);
        $this->assertSame(50.0, $informe['parametros']['sin_historial']);
        $this->assertTrue($informe['parametros']['penalizar_cobertura']);

        [$a, $b, $c] = $informe['cotizaciones'];

        $this->assertSame(78.8, round($a['puntaje'], 1));
        $this->assertSame(68.0, round($b['puntaje'], 1));
        $this->assertSame(65.1, round($c['puntaje'], 1));

        $this->assertSame(170.0, $a['precio']);
        $this->assertSame(3, $a['dias']);
        $this->assertSame(1.0, $a['cobertura']);
        $this->assertFalse($a['vencida']);
        $this->assertSame(100.0, $a['confiabilidad']);
        $this->assertSame(60.0, $b['confiabilidad']);

        $this->assertSame($fixture['a']['cotizacion']->id, $informe['recomendada_id']);
    }

    public function test_weights_that_do_not_add_up_to_one_are_normalized(): void
    {
        // Arrange
        $this->seed(ParametroGlobalSeeder::class);
        ParametroGlobal::where('nombre', 'compras_multicriterio_peso_precio')->update(['valor' => '1']);
        ParametroGlobal::where('nombre', 'compras_multicriterio_peso_entrega')->update(['valor' => '0.6']);
        ParametroGlobal::where('nombre', 'compras_multicriterio_peso_confiabilidad')->update(['valor' => '0.4']);

        $articulo = $this->articulo();
        $proveedor = $this->proveedor('Aceros del Sur');
        $solicitud = $this->solicitud($articulo);
        $this->cotizacion($solicitud, $proveedor, $articulo, 10.0, 10, 5);

        // Act
        $informe = $this->service()->paraSolicitud($solicitud->id);

        // Assert
        $this->assertSame(0.5, round($informe['parametros']['pesos']['precio'], 4));
        $this->assertSame(0.3, round($informe['parametros']['pesos']['entrega'], 4));
        $this->assertSame(0.2, round($informe['parametros']['pesos']['confiabilidad'], 4));
    }

    public function test_disabling_the_coverage_penalty_removes_the_multiplier(): void
    {
        // Arrange
        $this->seed(ParametroGlobalSeeder::class);
        ParametroGlobal::where('nombre', 'compras_multicriterio_confiabilidad_sin_historial')->update(['valor' => '100']);

        $articulo = $this->articulo();
        $proveedorA = $this->proveedor('Aceros del Sur');
        $proveedorB = $this->proveedor('Maderas Norte');
        $solicitud = $this->solicitud($articulo, 10);

        $this->cotizacion($solicitud, $proveedorA, $articulo, 10.0, 5, 5);
        $this->cotizacion($solicitud, $proveedorB, $articulo, 20.0, 10, 5);

        // Act
        $conPenalizacion = $this->service()->paraSolicitud($solicitud->id);

        ParametroGlobal::where('nombre', 'compras_multicriterio_penalizar_cobertura')->update(['valor' => '0']);
        $sinPenalizacion = $this->service()->paraSolicitud($solicitud->id);

        // Assert
        $cotizaciones = $solicitud->cotizaciones()->orderBy('id')->get();

        $this->assertSame(0.5, $conPenalizacion['cotizaciones'][0]['cobertura']);
        $this->assertSame(50.0, round($conPenalizacion['cotizaciones'][0]['puntaje'], 1));
        $this->assertSame($cotizaciones[1]->id, $conPenalizacion['recomendada_id']);

        $this->assertFalse($sinPenalizacion['parametros']['penalizar_cobertura']);
        $this->assertSame(0.5, $sinPenalizacion['cotizaciones'][0]['cobertura']);
        $this->assertSame(100.0, round($sinPenalizacion['cotizaciones'][0]['puntaje'], 1));
        $this->assertSame($cotizaciones[0]->id, $sinPenalizacion['recomendada_id']);
    }

    public function test_a_supplier_without_history_uses_the_neutral_reliability(): void
    {
        // Arrange
        $this->seed(ParametroGlobalSeeder::class);
        ParametroGlobal::where('nombre', 'compras_multicriterio_confiabilidad_sin_historial')->update(['valor' => '40']);

        $articulo = $this->articulo();
        $proveedor = $this->proveedor('Aceros del Sur');
        $solicitud = $this->solicitud($articulo);
        $this->cotizacion($solicitud, $proveedor, $articulo, 10.0, 10, 5);

        // Act
        $informe = $this->service()->paraSolicitud($solicitud->id);

        // Assert
        $this->assertSame(40.0, $informe['cotizaciones'][0]['confiabilidad']);
        $this->assertSame(40.0, $informe['cotizaciones'][0]['confiabilidad_norm']);
    }

    public function test_an_expired_promise_date_reports_zero_days_and_the_expired_flag(): void
    {
        // Arrange
        $this->seed(ParametroGlobalSeeder::class);

        $articulo = $this->articulo();
        $proveedor = $this->proveedor('Aceros del Sur');
        $solicitud = $this->solicitud($articulo);
        $this->cotizacion($solicitud, $proveedor, $articulo, 10.0, 10, -3);

        // Act
        $informe = $this->service()->paraSolicitud($solicitud->id);

        // Assert
        $this->assertSame(0, $informe['cotizaciones'][0]['dias']);
        $this->assertTrue($informe['cotizaciones'][0]['vencida']);
        // An expired promise is late, not instant: it must score zero, not the best mark.
        $this->assertSame(0.0, $informe['cotizaciones'][0]['entrega_norm']);
    }

    public function test_an_expired_quote_scores_worse_than_a_valid_one(): void
    {
        // Arrange
        $this->seed(ParametroGlobalSeeder::class);

        $articulo = $this->articulo();
        $vencido = $this->proveedor('Aceros del Sur');
        $enPlazo = $this->proveedor('Maderas Norte');
        $solicitud = $this->solicitud($articulo);

        $this->cotizacion($solicitud, $vencido, $articulo, 10.0, 10, -2);
        $this->cotizacion($solicitud, $enPlazo, $articulo, 10.0, 10, 5);

        // Act
        $informe = $this->service()->paraSolicitud($solicitud->id);
        $filas = collect($informe['cotizaciones'])->keyBy('proveedor');

        // Assert
        $this->assertSame(0.0, $filas['Aceros del Sur']['entrega_norm']);
        $this->assertSame(100.0, $filas['Maderas Norte']['entrega_norm']);
    }

    public function test_a_single_quote_has_no_recommendation(): void
    {
        // Arrange
        $this->seed(ParametroGlobalSeeder::class);

        $articulo = $this->articulo();
        $proveedor = $this->proveedor('Aceros del Sur');
        $solicitud = $this->solicitud($articulo);
        $this->cotizacion($solicitud, $proveedor, $articulo, 10.0, 10, 5);

        // Act
        $informe = $this->service()->paraSolicitud($solicitud->id);

        // Assert
        $this->assertCount(1, $informe['cotizaciones']);
        $this->assertNull($informe['recomendada_id']);
    }

    public function test_a_tie_at_the_top_score_has_no_recommendation(): void
    {
        // Arrange
        $this->seed(ParametroGlobalSeeder::class);

        $articulo = $this->articulo();
        $proveedorA = $this->proveedor('Aceros del Sur');
        $proveedorB = $this->proveedor('Maderas Norte');
        $solicitud = $this->solicitud($articulo);

        $this->cotizacion($solicitud, $proveedorA, $articulo, 10.0, 10, 5);
        $this->cotizacion($solicitud, $proveedorB, $articulo, 10.0, 10, 5);

        // Act
        $informe = $this->service()->paraSolicitud($solicitud->id);

        // Assert
        $this->assertSame(100.0, $informe['cotizaciones'][0]['precio_norm']);
        $this->assertSame(100.0, $informe['cotizaciones'][1]['precio_norm']);
        $this->assertSame($informe['cotizaciones'][0]['puntaje'], $informe['cotizaciones'][1]['puntaje']);
        $this->assertNull($informe['recomendada_id']);
    }

    public function test_missing_or_invalid_parameters_fall_back_to_defaults_and_clamp(): void
    {
        // Arrange
        ParametroGlobal::query()->create([
            'nombre' => 'compras_multicriterio_peso_precio',
            'valor' => '5',
            'categoria' => 'Compras',
        ]);
        ParametroGlobal::query()->create([
            'nombre' => 'compras_multicriterio_peso_entrega',
            'valor' => '-1',
            'categoria' => 'Compras',
        ]);
        ParametroGlobal::query()->create([
            'nombre' => 'compras_multicriterio_peso_confiabilidad',
            'valor' => 'no-numerico',
            'categoria' => 'Compras',
        ]);
        ParametroGlobal::query()->create([
            'nombre' => 'compras_multicriterio_confiabilidad_sin_historial',
            'valor' => '150',
            'categoria' => 'Compras',
        ]);
        ParametroGlobal::query()->create([
            'nombre' => 'compras_multicriterio_penalizar_cobertura',
            'valor' => '9',
            'categoria' => 'Compras',
        ]);

        $articulo = $this->articulo();
        $proveedor = $this->proveedor('Aceros del Sur');
        $solicitud = $this->solicitud($articulo);
        $this->cotizacion($solicitud, $proveedor, $articulo, 10.0, 10, 5);

        // Act
        $informe = $this->service()->paraSolicitud($solicitud->id);

        // Assert
        $this->assertSame(0.7692, round($informe['parametros']['pesos']['precio'], 4));
        $this->assertSame(0.0, round($informe['parametros']['pesos']['entrega'], 4));
        $this->assertSame(0.2308, round($informe['parametros']['pesos']['confiabilidad'], 4));
        $this->assertSame(100.0, $informe['parametros']['sin_historial']);
        $this->assertTrue($informe['parametros']['penalizar_cobertura']);
    }
}
