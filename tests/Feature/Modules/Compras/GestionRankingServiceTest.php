<?php

namespace Tests\Feature\Modules\Compras;

use App\Modules\Compras\Models\DesempenoProveedor;
use App\Modules\Compras\Models\Incidencia;
use App\Modules\Compras\Models\OrdenDeCompra;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Services\GestionRankingService;
use App\Modules\Usuarios\Models\ParametroGlobal;
use Database\Factories\SolicitudDeDisponibilidadFactory;
use Database\Seeders\ParametroGlobalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GestionRankingServiceTest extends TestCase
{
    use RefreshDatabase;

    private int $secuencia = 0;

    private function service(): GestionRankingService
    {
        return app(GestionRankingService::class);
    }

    private function proveedor(string $razonSocial, string $estado = 'Activo'): Proveedor
    {
        $this->secuencia++;

        return Proveedor::factory()->create([
            'razon_social' => $razonSocial,
            'cuit' => sprintf('30-%08d-1', $this->secuencia),
            'email' => sprintf('proveedor%d@example.test', $this->secuencia),
            'estado' => $estado,
        ]);
    }

    /**
     * @param  array<int, string>  $incidencias
     */
    private function entrega(Proveedor $proveedor, array $incidencias = [], ?Carbon $fecha = null): DesempenoProveedor
    {
        $this->secuencia++;

        $solicitud = SolicitudDeDisponibilidadFactory::new()->create([
            'proveedor_id' => $proveedor->id,
        ]);

        $orden = OrdenDeCompra::factory()->create([
            'numero_oc' => sprintf('OC-TEST-%04d', $this->secuencia),
            'proveedor_id' => $proveedor->id,
            'solicitud_de_disponibilidad_id' => $solicitud->id,
        ]);

        foreach ($incidencias as $tipo) {
            Incidencia::create([
                'proveedor_id' => $proveedor->id,
                'orden_de_compra_id' => $orden->id,
                'tipo' => $tipo,
                'fecha' => $fecha ?? today(),
                'detalle' => 'Detalle de prueba',
            ]);
        }

        return DesempenoProveedor::create([
            'proveedor_id' => $proveedor->id,
            'orden_de_compra_id' => $orden->id,
            'tiempo_entrega_real' => 3,
            'fecha_registro' => $fecha ?? today(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function filaDe(string $razonSocial, array $resultado): array
    {
        foreach ($resultado['filas'] as $fila) {
            if ($fila['proveedor'] === $razonSocial) {
                return $fila;
            }
        }

        $this->fail("No ranking row found for {$razonSocial}.");
    }

    public function test_a_supplier_with_four_perfect_deliveries_scores_one_hundred(): void
    {
        // Arrange
        $this->seed(ParametroGlobalSeeder::class);
        $proveedor = $this->proveedor('Aceros del Sur');

        foreach (range(1, 4) as $ignorado) {
            $this->entrega($proveedor);
        }

        // Act
        $resultado = $this->service()->listar();

        // Assert
        $fila = $this->filaDe('Aceros del Sur', $resultado);
        $this->assertSame(4, $fila['entregas']);
        $this->assertSame(4, $fila['perfectas']);
        $this->assertSame(0, $fila['retrasos']);
        $this->assertSame(0, $fila['faltantes']);
        $this->assertSame(0, $fila['roturas']);
        $this->assertSame(100.0, $fila['puntaje']);
    }

    public function test_one_late_delivery_uses_the_late_weight(): void
    {
        // Arrange
        $this->seed(ParametroGlobalSeeder::class);
        $proveedor = $this->proveedor('Maderas Norte');

        $this->entrega($proveedor, ['Retraso']);

        foreach (range(1, 3) as $ignorado) {
            $this->entrega($proveedor);
        }

        // Act
        $fila = $this->filaDe('Maderas Norte', $this->service()->listar());

        // Assert
        $this->assertSame(4, $fila['entregas']);
        $this->assertSame(3, $fila['perfectas']);
        $this->assertSame(1, $fila['retrasos']);
        $this->assertSame(90.0, $fila['puntaje']);
    }

    public function test_a_short_delivery_uses_the_shortage_weight(): void
    {
        // Arrange
        $this->seed(ParametroGlobalSeeder::class);
        $proveedor = $this->proveedor('Clavos del Litoral');

        $this->entrega($proveedor, ['Faltante']);

        foreach (range(1, 3) as $ignorado) {
            $this->entrega($proveedor);
        }

        // Act
        $fila = $this->filaDe('Clavos del Litoral', $this->service()->listar());

        // Assert
        $this->assertSame(4, $fila['entregas']);
        $this->assertSame(3, $fila['perfectas']);
        $this->assertSame(1, $fila['faltantes']);
        $this->assertSame(82.5, $fila['puntaje']);
    }

    public function test_a_broken_delivery_uses_the_breakage_weight(): void
    {
        // Arrange
        $this->seed(ParametroGlobalSeeder::class);
        $proveedor = $this->proveedor('Vidrios Paraná');

        $this->entrega($proveedor, ['Rotura']);

        foreach (range(1, 3) as $ignorado) {
            $this->entrega($proveedor);
        }

        // Act
        $fila = $this->filaDe('Vidrios Paraná', $this->service()->listar());

        // Assert
        $this->assertSame(4, $fila['entregas']);
        $this->assertSame(3, $fila['perfectas']);
        $this->assertSame(1, $fila['roturas']);
        $this->assertSame(75.0, $fila['puntaje']);
    }

    public function test_the_worst_incidence_of_an_order_prevails(): void
    {
        // Arrange
        $this->seed(ParametroGlobalSeeder::class);
        $proveedor = $this->proveedor('Hierros del Sur');

        $this->entrega($proveedor, ['Retraso', 'Faltante']);
        $this->entrega($proveedor, ['Faltante', 'Rotura']);
        $this->entrega($proveedor, ['Retraso']);

        // Act
        $fila = $this->filaDe('Hierros del Sur', $this->service()->listar());

        // Assert
        $this->assertSame(3, $fila['entregas']);
        $this->assertSame(0, $fila['perfectas']);
        $this->assertSame(1, $fila['retrasos']);
        $this->assertSame(1, $fila['faltantes']);
        $this->assertSame(1, $fila['roturas']);
        $this->assertSame(30.0, $fila['puntaje']);
    }

    public function test_the_window_excludes_old_deliveries(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2026-11-07 12:00:00'));
        $this->seed(ParametroGlobalSeeder::class);
        $proveedor = $this->proveedor('Aceros del Sur');

        $this->entrega($proveedor, ['Rotura'], Carbon::parse('2025-01-01 09:00:00'));
        $this->entrega($proveedor, [], Carbon::parse('2026-11-01 09:00:00'));

        // Act
        $fila = $this->filaDe('Aceros del Sur', $this->service()->listar());

        // Assert
        $this->assertSame(1, $fila['entregas']);
        $this->assertSame(1, $fila['perfectas']);
        $this->assertSame(0, $fila['roturas']);
        $this->assertSame(100.0, $fila['puntaje']);
    }

    public function test_active_suppliers_without_deliveries_are_listed_as_without_history(): void
    {
        // Arrange
        $this->seed(ParametroGlobalSeeder::class);
        $conHistorial = $this->proveedor('Con historial');
        $this->entrega($conHistorial);

        $this->proveedor('Sin historial');
        $this->proveedor('Proveedor inactivo', 'Inactivo');

        // Act
        $resultado = $this->service()->listar();

        // Assert
        $this->assertCount(1, $resultado['filas']);
        $this->assertSame('Con historial', $resultado['filas'][0]['proveedor']);
        $this->assertCount(1, $resultado['sin_historial']);
        $this->assertSame('Sin historial', $resultado['sin_historial'][0]['proveedor']);
    }

    public function test_missing_parameters_fall_back_to_the_defaults(): void
    {
        // Arrange
        $proveedor = $this->proveedor('Aceros del Sur');
        $this->entrega($proveedor);

        // Act
        $resultado = $this->service()->listar();

        // Assert
        $this->assertSame([
            'perfecta' => 1.0,
            'retraso' => 0.6,
            'faltante' => 0.3,
            'rotura' => 0.0,
        ], $resultado['parametros']['pesos']);
        $this->assertSame(12, $resultado['parametros']['ventana_meses']);
    }

    public function test_weights_are_clamped_to_the_zero_one_range(): void
    {
        // Arrange
        $this->seed(ParametroGlobalSeeder::class);

        ParametroGlobal::where('nombre', 'compras_ranking_peso_perfecta')->update(['valor' => '2']);
        ParametroGlobal::where('nombre', 'compras_ranking_peso_retraso')->update(['valor' => '-1']);
        ParametroGlobal::where('nombre', 'compras_ranking_peso_faltante')->update(['valor' => '0.5']);
        ParametroGlobal::where('nombre', 'compras_ranking_peso_rotura')->update(['valor' => '7']);
        ParametroGlobal::where('nombre', 'compras_ranking_ventana_meses')->update(['valor' => '0']);

        $proveedor = $this->proveedor('Aceros del Sur');
        $this->entrega($proveedor);

        // Act
        $resultado = $this->service()->listar();

        // Assert
        $this->assertSame([
            'perfecta' => 1.0,
            'retraso' => 0.0,
            'faltante' => 0.5,
            'rotura' => 1.0,
        ], $resultado['parametros']['pesos']);
        $this->assertSame(1, $resultado['parametros']['ventana_meses']);
    }

    public function test_the_window_is_capped_at_sixty_months(): void
    {
        // Arrange
        $this->seed(ParametroGlobalSeeder::class);

        ParametroGlobal::where('nombre', 'compras_ranking_ventana_meses')->update(['valor' => '999']);

        $proveedor = $this->proveedor('Aceros del Sur');
        $this->entrega($proveedor);

        // Act
        $resultado = $this->service()->listar();

        // Assert
        $this->assertSame(60, $resultado['parametros']['ventana_meses']);
    }

    public function test_rows_are_ordered_by_score_and_then_by_business_name(): void
    {
        // Arrange
        $this->seed(ParametroGlobalSeeder::class);

        $zeta = $this->proveedor('Zeta');
        $this->entrega($zeta);
        $this->entrega($zeta);

        $alfa = $this->proveedor('Alfa');
        $this->entrega($alfa);
        $this->entrega($alfa);

        $beta = $this->proveedor('Beta');
        $this->entrega($beta);
        $this->entrega($beta, ['Rotura']);

        // Act
        $resultado = $this->service()->listar();

        // Assert
        $this->assertSame(
            ['Alfa', 'Zeta', 'Beta'],
            array_column($resultado['filas'], 'proveedor'),
        );
    }
}
