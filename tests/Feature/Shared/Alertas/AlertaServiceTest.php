<?php

namespace Tests\Feature\Shared\Alertas;

use App\Shared\Alertas\Services\AlertaService;
use Database\Factories\ArticuloFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertaServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_emits_an_alert_with_context(): void
    {
        // Arrange
        $articulo = ArticuloFactory::new()->create();
        $service = new AlertaService;

        // Act
        $alerta = $service->emitirAlerta(
            'Reposición',
            ['descripcion' => 'Stock por debajo del mínimo', 'articulo_id' => $articulo->id],
            'Inventario',
        );

        // Assert
        $this->assertDatabaseHas('alertas', [
            'id' => $alerta->id,
            'tipo' => 'Reposición',
            'origen' => 'Inventario',
            'articulo_id' => $articulo->id,
        ]);
        $this->assertSame('Stock por debajo del mínimo', $alerta->descripcion);
    }

    public function test_it_emits_an_alert_without_context_references(): void
    {
        // Arrange
        $service = new AlertaService;

        // Act
        $alerta = $service->emitirAlerta('Técnica', ['descripcion' => 'Timeout del motor'], 'Predictivo');

        // Assert
        $this->assertDatabaseHas('alertas', [
            'id' => $alerta->id,
            'tipo' => 'Técnica',
            'origen' => 'Predictivo',
            'articulo_id' => null,
            'orden_de_produccion_id' => null,
            'maquinaria_id' => null,
        ]);
    }
}
