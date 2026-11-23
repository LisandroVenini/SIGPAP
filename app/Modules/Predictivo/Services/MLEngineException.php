<?php

namespace App\Modules\Predictivo\Services;

use RuntimeException;

class MLEngineException extends RuntimeException
{
    public function __construct(
        public readonly string $operacion,
        public readonly ?string $codigo,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function procesoFallido(string $operacion, ?int $codigoDeSalida, string $error): self
    {
        return new self(
            $operacion,
            'proceso_fallido',
            sprintf(
                'The ML engine failed (exit code %s): %s',
                $codigoDeSalida !== null ? (string) $codigoDeSalida : 'unknown',
                trim($error),
            ),
        );
    }

    public static function timeout(string $operacion, int $segundos): self
    {
        return new self($operacion, 'timeout', sprintf('The ML engine timed out after %d seconds.', $segundos));
    }

    public static function respuestaInvalida(string $operacion): self
    {
        return new self($operacion, 'respuesta_invalida', 'The ML engine returned an invalid response.');
    }

    public static function errorDeDominio(string $operacion, ?string $codigo, string $mensaje): self
    {
        return new self(
            $operacion,
            $codigo,
            $mensaje !== '' ? $mensaje : 'The ML engine reported an error.',
        );
    }
}
