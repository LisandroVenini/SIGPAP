<?php

namespace Tests\Feature\Modules\Compras;

use App\Modules\Compras\Models\Cotizacion;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Models\SolicitudDeCotizacion;
use App\Modules\Compras\Queries\InformeMulticriterioQuery;
use App\Modules\Inventario\Models\Articulo;
use Database\Factories\CotizacionFactory;
use Database\Factories\LineaCotizacionFactory;
use Database\Factories\SolicitudDeCotizacionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InformeMulticriterioQueryTest extends TestCase
{
    use RefreshDatabase;

    private function informeQuery(): InformeMulticriterioQuery
    {
        return app(InformeMulticriterioQuery::class);
    }

    /**
     * @return array{solicitud: SolicitudDeCotizacion, articuloA: Articulo, articuloB: Articulo, cotizacion: Cotizacion}
     */
    private function contexto(): array
    {
        $articuloA = Articulo::factory()->create(['descripcion' => 'Tabla de pino']);
        $articuloB = Articulo::factory()->create(['descripcion' => 'Tornillo']);

        $solicitud = SolicitudDeCotizacionFactory::new()->create(['estado' => 'Enviada']);
        $solicitud->lineas()->create(['articulo_id' => $articuloA->id, 'cantidad_solicitada' => 10]);
        $solicitud->lineas()->create(['articulo_id' => $articuloB->id, 'cantidad_solicitada' => 4]);

        $proveedor = Proveedor::factory()->create(['razon_social' => 'Aceros del Sur']);
        $cotizacion = CotizacionFactory::new()->create([
            'solicitud_de_cotizacion_id' => $solicitud->id,
            'solicitud_de_disponibilidad_id' => null,
            'proveedor_id' => $proveedor->id,
        ]);

        LineaCotizacionFactory::new()->create([
            'cotizacion_id' => $cotizacion->id,
            'articulo_id' => $articuloA->id,
            'precio_unitario' => 12.5,
            'cantidad_ofertada' => 8,
        ]);
        LineaCotizacionFactory::new()->create([
            'cotizacion_id' => $cotizacion->id,
            'articulo_id' => $articuloB->id,
            'precio_unitario' => 2,
            'cantidad_ofertada' => 4,
        ]);

        return compact('solicitud', 'articuloA', 'articuloB', 'cotizacion');
    }

    public function test_it_returns_quotes_with_their_supplier_lines_and_the_requested_quantities(): void
    {
        // Arrange
        $contexto = $this->contexto();

        // Act
        $cotizaciones = $this->informeQuery()->cotizacionesDeSolicitud($contexto['solicitud']->id);

        // Assert
        $this->assertCount(1, $cotizaciones);

        $cotizacion = $cotizaciones->first();

        $this->assertTrue($cotizacion->relationLoaded('proveedor'));
        $this->assertSame('Aceros del Sur', $cotizacion->proveedor->razon_social);
        $this->assertTrue($cotizacion->relationLoaded('lineas'));
        $this->assertCount(2, $cotizacion->lineas);

        $this->assertTrue($cotizacion->relationLoaded('solicitudDeCotizacion'));
        $this->assertTrue($cotizacion->solicitudDeCotizacion->relationLoaded('lineas'));

        $cantidades = $cotizacion->solicitudDeCotizacion->lineas
            ->pluck('cantidad_solicitada', 'articulo_id')
            ->map(fn ($cantidad): float => (float) $cantidad)
            ->all();

        $this->assertSame([$contexto['articuloA']->id => 10.0, $contexto['articuloB']->id => 4.0], $cantidades);
    }

    public function test_loading_the_report_does_not_grow_the_query_count_with_more_lines(): void
    {
        // Arrange
        $contexto = $this->contexto();

        DB::enableQueryLog();
        $this->informeQuery()->cotizacionesDeSolicitud($contexto['solicitud']->id);
        $consultasBase = count(DB::getQueryLog());
        DB::flushQueryLog();

        foreach (range(1, 5) as $ignorado) {
            LineaCotizacionFactory::new()->create([
                'cotizacion_id' => $contexto['cotizacion']->id,
            ]);
        }

        DB::flushQueryLog();

        // Act
        $this->informeQuery()->cotizacionesDeSolicitud($contexto['solicitud']->id);

        // Assert
        $this->assertLessThanOrEqual(6, $consultasBase);
        $this->assertSame($consultasBase, count(DB::getQueryLog()));
    }
}
