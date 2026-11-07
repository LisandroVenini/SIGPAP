<?php

namespace App\Modules\Compras\Enums;

enum EstadoSolicitudDeCotizacion: string
{
    case Borrador = 'Borrador';
    case Enviada = 'Enviada';
    case Adjudicada = 'Adjudicada';
    case Anulada = 'Anulada';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
