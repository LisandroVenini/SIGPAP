<?php

namespace Tests\Feature\Modules\Predictivo;

use App\Modules\Predictivo\Services\MLEngine;
use App\Modules\Predictivo\Services\MLEngineException;
use App\Modules\Predictivo\Services\ProcesoMLEngine;
use Tests\TestCase;

class ProcesoMLEngineTest extends TestCase
{
    /**
     * Build the engine against a stdlib-only stub so the suite never needs the
     * Python virtualenv.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function engine(array $overrides = []): MLEngine
    {
        config()->set('predictivo.ml_engine', array_merge([
            'binary' => 'python3',
            'script' => base_path('tests/Fixtures/Predictivo/engine_stub.py'),
            'timeout' => 10,
            'training_timeout' => 10,
        ], $overrides));

        return app(MLEngine::class);
    }

    public function test_the_container_resolves_the_contract_to_the_process_engine(): void
    {
        $this->assertInstanceOf(ProcesoMLEngine::class, $this->engine());
    }

    public function test_it_runs_the_operation_and_returns_the_result(): void
    {
        $resultado = $this->engine()->predecirTasaDeRendimiento([
            'etapa_id' => 3,
            'muestras' => [],
        ]);

        $this->assertSame('predecir_trr', $resultado['operacion']);
        $this->assertSame(3, $resultado['datos']['etapa_id']);
    }

    public function test_training_runs_through_the_same_protocol(): void
    {
        $resultado = $this->engine()->entrenar(['etapa_id' => 3]);

        $this->assertSame('entrenar', $resultado['operacion']);
    }

    public function test_it_throws_when_the_process_fails(): void
    {
        $this->expectException(MLEngineException::class);

        $this->engine()->predecirTasaDeRendimiento(['escenario' => 'falla']);
    }

    public function test_it_throws_when_the_response_is_not_json(): void
    {
        $this->expectException(MLEngineException::class);

        $this->engine()->predecirTasaDeRendimiento(['escenario' => 'basura']);
    }

    public function test_it_surfaces_the_domain_error_code(): void
    {
        try {
            $this->engine()->predecirTasaDeRendimiento(['escenario' => 'error']);

            $this->fail('Expected MLEngineException.');
        } catch (MLEngineException $error) {
            $this->assertSame('datos_invalidos', $error->codigo);
            $this->assertSame('predecir_trr', $error->operacion);
        }
    }

    public function test_it_throws_when_the_engine_times_out(): void
    {
        $engine = $this->engine(['timeout' => 1]);

        $this->expectException(MLEngineException::class);

        $engine->predecirTasaDeRendimiento(['escenario' => 'lento']);
    }
}
