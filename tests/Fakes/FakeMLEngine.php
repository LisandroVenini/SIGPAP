<?php

namespace Tests\Fakes;

use App\Modules\Predictivo\Services\MLEngine;
use App\Modules\Predictivo\Services\MLEngineException;

/**
 * Deterministic, offline engine double for the test suite.
 */
final class FakeMLEngine implements MLEngine
{
    /** @var array<int, array{metodo: string, datos: array<string, mixed>}> */
    public array $llamadas = [];

    /**
     * @param  array<string, mixed>  $resultado
     */
    public function __construct(
        private array $resultado = [],
        private ?MLEngineException $error = null,
    ) {}

    public function predecirTasaDeRendimiento(array $datos): array
    {
        $this->llamadas[] = ['metodo' => 'predecir_trr', 'datos' => $datos];

        if ($this->error !== null) {
            throw $this->error;
        }

        return $this->resultado + [
            'etapa_id' => (int) ($datos['etapa_id'] ?? 0),
            'valor' => 1.0,
            'desviacion' => 0.0,
            'dias_proyectados' => 0.0,
            'origen' => 'estandar',
            'metricas' => ['r2' => null, 'mae' => null, 'n_muestras' => 0],
        ];
    }

    public function evaluarRiesgoSuministro(array $datos): array
    {
        $this->llamadas[] = ['metodo' => 'evaluar_riesgo', 'datos' => $datos];

        if ($this->error !== null) {
            throw $this->error;
        }

        return $this->resultado + [
            'criticidad' => [],
            'evaluados' => count($datos['insumos'] ?? []),
        ];
    }

    public function resolverDisponibilidad(array $datos): array
    {
        $this->llamadas[] = ['metodo' => 'resolver_disponibilidad', 'datos' => $datos];

        if ($this->error !== null) {
            throw $this->error;
        }

        return $this->resultado + [
            'escenario' => 'Ideal',
            'motivos' => [],
            'desviacion_precio' => null,
            'dias_de_atraso' => null,
        ];
    }

    public function entrenar(array $datos): array
    {
        $this->llamadas[] = ['metodo' => 'entrenar', 'datos' => $datos];

        if ($this->error !== null) {
            throw $this->error;
        }

        return $this->resultado + [
            'etapa_id' => (int) ($datos['etapa_id'] ?? 0),
            'metricas' => ['r2' => null, 'mae' => 0.0, 'n_muestras' => 0],
            'metricas_anteriores' => null,
            'delta' => ['r2' => null, 'mae' => null],
            'n_muestras' => 0,
            'guardado' => true,
        ];
    }
}
