<?php

namespace Tests\Feature\Modules\Compras;

use App\Modules\Compras\Models\Cotizacion;
use App\Modules\Compras\Models\DesempenoProveedor;
use App\Modules\Compras\Models\LineaCotizacion;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Queries\LeadTimePorArticuloQuery;
use App\Modules\Inventario\Models\Articulo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadTimePorArticuloQueryTest extends TestCase
{
    use RefreshDatabase;

    private function consulta(): LeadTimePorArticuloQuery
    {
        return app(LeadTimePorArticuloQuery::class);
    }

    private function proveedor(string $razonSocial): Proveedor
    {
        return Proveedor::factory()->create(['razon_social' => $razonSocial]);
    }

    /**
     * @param  array<int, int>  $tiempos
     */
    private function desempeno(Proveedor $proveedor, array $tiempos): void
    {
        foreach ($tiempos as $dias) {
            DesempenoProveedor::factory()->create([
                'proveedor_id' => $proveedor->id,
                'tiempo_entrega_real' => $dias,
                'fecha_registro' => now()->subDays(5)->toDateString(),
            ]);
        }
    }

    private function cotizo(Proveedor $proveedor, Articulo $articulo): void
    {
        $cotizacion = Cotizacion::factory()->create([
            'proveedor_id' => $proveedor->id,
            'fecha' => now()->toDateString(),
        ]);

        LineaCotizacion::factory()->create([
            'cotizacion_id' => $cotizacion->id,
            'articulo_id' => $articulo->id,
        ]);
    }

    public function test_it_computes_the_median_lead_time_per_supplier_and_article(): void
    {
        $articuloUno = Articulo::factory()->create();
        $articuloDos = Articulo::factory()->create();

        $proveedorUno = $this->proveedor('Aceros del Sur');
        $proveedorDos = $this->proveedor('Maderas del Norte');

        $this->desempeno($proveedorUno, [4, 6, 5]);
        $this->desempeno($proveedorDos, [10, 12]);

        $this->cotizo($proveedorUno, $articuloUno);
        $this->cotizo($proveedorDos, $articuloDos);

        $resumen = $this->consulta()->resumen();

        $this->assertSame(5.0, $resumen['por_articulo'][$articuloUno->id]['lead_time_dias']);
        $this->assertSame([$proveedorUno->id], $resumen['por_articulo'][$articuloUno->id]['proveedor_ids']);
        $this->assertSame(11.0, $resumen['por_articulo'][$articuloDos->id]['lead_time_dias']);
        $this->assertSame(5.0, $resumen['por_proveedor'][$proveedorUno->id]['lead_time_dias']);
        $this->assertSame(3, $resumen['por_proveedor'][$proveedorUno->id]['entregas']);
    }

    public function test_the_global_median_is_the_fallback_when_the_quoting_supplier_has_no_history(): void
    {
        $articulo = Articulo::factory()->create();
        $conHistorial = $this->proveedor('Con historial');
        $sinHistorial = $this->proveedor('Sin historial');

        $this->desempeno($conHistorial, [8, 10]);
        $this->cotizo($sinHistorial, $articulo);

        $resumen = $this->consulta()->resumen();

        $this->assertSame(9.0, $resumen['global']);
        $this->assertSame(9.0, $resumen['por_articulo'][$articulo->id]['lead_time_dias']);
    }

    public function test_an_article_never_quoted_has_no_entry(): void
    {
        $articulo = Articulo::factory()->create();
        $proveedor = $this->proveedor('Aceros del Sur');
        $this->desempeno($proveedor, [5]);

        $resumen = $this->consulta()->resumen();

        $this->assertArrayNotHasKey($articulo->id, $resumen['por_articulo']);
        $this->assertSame(5.0, $resumen['global']);
    }
}
