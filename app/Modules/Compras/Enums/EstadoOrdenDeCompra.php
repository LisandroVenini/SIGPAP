<?php

namespace App\Modules\Compras\Enums;

enum EstadoOrdenDeCompra: string
{
    case Emitida = 'Emitida';
    case Confirmada = 'Confirmada';
    case Recibida = 'Recibida';
    case Anulada = 'Anulada';
    case Rechazada = 'Rechazada';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
