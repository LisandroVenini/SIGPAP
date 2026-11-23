<?php

namespace App\Modules\Predictivo\Services;

use RuntimeException;

/**
 * A permanent prediction error: retrying will not fix it.
 */
class PrediccionDeTiemposException extends RuntimeException
{
    public function __construct(
        public readonly string $codigo,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function ordenNoDisponible(int $ordenId): self
    {
        return new self(
            'orden_no_disponible',
            sprintf('The production order %d is not in the schedule any more.', $ordenId),
        );
    }

    public static function etapaFueraDeRuta(int $etapaId, int $articuloId): self
    {
        return new self(
            'etapa_fuera_de_ruta',
            sprintf('The stage %d does not belong to the route sheet of the product %d.', $etapaId, $articuloId),
        );
    }

    public static function datosInvalidos(string $detalle): self
    {
        return new self('datos_invalidos', $detalle);
    }
}
