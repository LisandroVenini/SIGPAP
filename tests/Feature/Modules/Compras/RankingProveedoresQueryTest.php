<?php

namespace Tests\Feature\Modules\Compras;

use App\Modules\Compras\Models\DesempenoProveedor;
use App\Modules\Compras\Models\Incidencia;
use App\Modules\Compras\Models\OrdenDeCompra;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Queries\RankingProveedoresQuery;
use Database\Factories\SolicitudDeDisponibilidadFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RankingProveedoresQueryTest extends TestCase
{
    use RefreshDatabase;

    private int $secuencia = 0;

    private function rankingQuery(): RankingProveedoresQuery
    {
        return app(RankingProveedoresQuery::class);
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

    private function entrega(Proveedor $proveedor, Carbon $fecha): DesempenoProveedor
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

        return DesempenoProveedor::create([
            'proveedor_id' => $proveedor->id,
            'orden_de_compra_id' => $orden->id,
            'tiempo_entrega_real' => 3,
            'fecha_registro' => $fecha,
        ]);
    }

    public function test_deliveries_inside_the_window_are_filtered_and_grouped_by_supplier(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2026-11-07 12:00:00'));

        $proveedor = $this->proveedor('Aceros del Sur');
        $otroProveedor = $this->proveedor('Maderas Norte');

        $this->entrega($proveedor, Carbon::parse('2025-01-01 09:00:00'));
        $this->entrega($proveedor, Carbon::parse('2026-10-01 09:00:00'));
        $this->entrega($otroProveedor, Carbon::parse('2026-11-01 09:00:00'));

        // Act
        $entregas = $this->rankingQuery()->entregasEnVentana(12);
        $agrupadas = $entregas->groupBy(fn (DesempenoProveedor $entrega): int => (int) $entrega->proveedor_id);

        // Assert
        $this->assertCount(2, $entregas);
        $this->assertSame(
            ['2026-10-01', '2026-11-01'],
            $entregas->pluck('fecha_registro')->map(fn (Carbon $fecha): string => $fecha->toDateString())->values()->all(),
        );
        $this->assertCount(2, $agrupadas);
        $this->assertCount(1, $agrupadas->get((int) $proveedor->id));
        $this->assertCount(1, $agrupadas->get((int) $otroProveedor->id));
    }

    public function test_deliveries_load_their_order_and_incidents_without_lazy_loading(): void
    {
        // Arrange
        $proveedor = $this->proveedor('Aceros del Sur');

        $solicitud = SolicitudDeDisponibilidadFactory::new()->create([
            'proveedor_id' => $proveedor->id,
        ]);

        $orden = OrdenDeCompra::factory()->create([
            'numero_oc' => 'OC-TEST-0001',
            'proveedor_id' => $proveedor->id,
            'solicitud_de_disponibilidad_id' => $solicitud->id,
        ]);

        Incidencia::create([
            'proveedor_id' => $proveedor->id,
            'orden_de_compra_id' => $orden->id,
            'tipo' => 'Retraso',
            'fecha' => today(),
            'detalle' => 'Demora en la entrega',
        ]);

        DesempenoProveedor::create([
            'proveedor_id' => $proveedor->id,
            'orden_de_compra_id' => $orden->id,
            'tiempo_entrega_real' => 5,
            'fecha_registro' => today(),
        ]);

        // Act
        $entrega = $this->rankingQuery()->entregasEnVentana(12)->first();

        // Assert
        $this->assertNotNull($entrega);
        $this->assertTrue($entrega->relationLoaded('ordenDeCompra'));
        $this->assertTrue($entrega->ordenDeCompra->relationLoaded('incidencias'));
        $this->assertCount(1, $entrega->ordenDeCompra->incidencias);
        $this->assertSame('Retraso', $entrega->ordenDeCompra->incidencias->first()->tipo);
    }

    public function test_active_suppliers_are_returned_ordered_by_business_name(): void
    {
        // Arrange
        $beta = $this->proveedor('Beta');
        $alfa = $this->proveedor('Alfa');
        $this->proveedor('Gamma', 'Inactivo');

        // Act
        $activos = Proveedor::query()->activos()->get(['id', 'razon_social']);

        // Assert
        $this->assertSame(['Alfa', 'Beta'], $activos->pluck('razon_social')->all());
        $this->assertSame(
            [(int) $alfa->id, (int) $beta->id],
            $activos->pluck('id')->map(fn ($id): int => (int) $id)->all(),
        );
    }
}
