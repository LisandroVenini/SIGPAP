<?php

namespace App\Modules\Predictivo\Services;

use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Process;

/**
 * Runs the Python engine as a short-lived process over stdin/stdout.
 * No HTTP, no persistent connection, no database access from the engine.
 */
class ProcesoMLEngine implements MLEngine
{
    /**
     * @param  array{binary: string, script: string, timeout: int, training_timeout: int}  $config
     */
    public function __construct(private readonly array $config) {}

    public function predecirTasaDeRendimiento(array $datos): array
    {
        return $this->ejecutar('predecir_trr', $datos, (int) $this->config['timeout']);
    }

    public function entrenar(array $datos): array
    {
        return $this->ejecutar('entrenar', $datos, (int) $this->config['training_timeout']);
    }

    public function evaluarRiesgoSuministro(array $datos): array
    {
        return $this->ejecutar('evaluar_riesgo', $datos, (int) $this->config['timeout']);
    }

    public function resolverDisponibilidad(array $datos): array
    {
        return $this->ejecutar('resolver_disponibilidad', $datos, (int) $this->config['timeout']);
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     *
     * @throws MLEngineException
     */
    private function ejecutar(string $operacion, array $datos, int $timeout): array
    {
        $solicitud = json_encode(['operacion' => $operacion, 'datos' => $datos], JSON_THROW_ON_ERROR);

        try {
            $proceso = Process::timeout($timeout)
                ->input($solicitud)
                ->run([$this->ruta($this->config['binary']), $this->ruta($this->config['script'])]);
        } catch (ProcessTimedOutException) {
            throw MLEngineException::timeout($operacion, $timeout);
        }

        if ($proceso->failed()) {
            throw MLEngineException::procesoFallido($operacion, $proceso->exitCode(), $proceso->errorOutput());
        }

        $respuesta = json_decode($proceso->output(), true);

        if (! is_array($respuesta) || ! isset($respuesta['estado'])) {
            throw MLEngineException::respuestaInvalida($operacion);
        }

        if ($respuesta['estado'] === 'error') {
            throw MLEngineException::errorDeDominio(
                $operacion,
                isset($respuesta['codigo']) ? (string) $respuesta['codigo'] : null,
                isset($respuesta['mensaje']) ? (string) $respuesta['mensaje'] : '',
            );
        }

        if ($respuesta['estado'] !== 'ok' || ! isset($respuesta['resultado']) || ! is_array($respuesta['resultado'])) {
            throw MLEngineException::respuestaInvalida($operacion);
        }

        return $respuesta['resultado'];
    }

    /**
     * Resolve a configured path, leaving bare binaries from the PATH untouched.
     */
    private function ruta(string $valor): string
    {
        if (str_starts_with($valor, DIRECTORY_SEPARATOR)) {
            return $valor;
        }

        return str_contains($valor, DIRECTORY_SEPARATOR) ? base_path($valor) : $valor;
    }
}
