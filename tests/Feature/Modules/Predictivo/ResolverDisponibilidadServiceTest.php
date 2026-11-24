<?php

namespace Tests\Feature\Modules\Predictivo;

use App\Modules\Predictivo\Services\MLEngine;
use App\Modules\Predictivo\Services\ResolverDisponibilidadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakeMLEngine;
use Tests\TestCase;

class ResolverDisponibilidadServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $resultado
     */
    private function fake(array $resultado): FakeMLEngine
    {
        $fake = new FakeMLEngine($resultado);
        $this->app->instance(MLEngine::class, $fake);

        return $fake;
    }

    /**
     * @return array{cantidad_disponible: float, precio_unitario: float, precio_referencia: float|null, umbral: float, fecha_prometida: string, fecha_requerida: string|null}
     */
    private function respuesta(): array
    {
        return [
            'cantidad_disponible' => 40.0,
            'precio_unitario' => 100.0,
            'precio_referencia' => 100.0,
            'umbral' => 0.15,
            'fecha_prometida' => '2026-11-28',
            'fecha_requerida' => '2026-12-01',
        ];
    }

    public function test_it_returns_an_ideal_recommendation(): void
    {
        $fake = $this->fake(['escenario' => 'Ideal', 'motivos' => []]);

        $resultado = app(ResolverDisponibilidadService::class)->resolver($this->respuesta());

        $this->assertSame('Ideal', $resultado['escenario']);
        $this->assertSame('Confirmar y emitir la orden de compra.', $resultado['recomendacion']);
        $this->assertSame('resolver_disponibilidad', $fake->llamadas[0]['metodo']);
        $this->assertSame(0.15, $fake->llamadas[0]['datos']['umbral']);
    }

    public function test_it_recommends_the_next_supplier_on_no_stock(): void
    {
        $this->fake(['escenario' => 'Sin Stock', 'motivos' => ['sin_stock']]);

        $resultado = app(ResolverDisponibilidadService::class)->resolver($this->respuesta());

        $this->assertStringContainsString('siguiente del ranking', $resultado['recomendacion']);
    }

    public function test_it_explains_a_strong_variation(): void
    {
        $this->fake([
            'escenario' => 'Variación Fuerte',
            'motivos' => ['precio_sobre_umbral', 'fecha_fuera_del_cronograma'],
        ]);

        $resultado = app(ResolverDisponibilidadService::class)->resolver($this->respuesta());

        $this->assertStringContainsString('el precio supera el umbral esperado', $resultado['recomendacion']);
        $this->assertStringContainsString('la fecha prometida no llega al cronograma', $resultado['recomendacion']);
    }
}
