<?php

namespace App\Modules\Compras\Enums;

enum EstadoSolicitudDeDisponibilidad: string
{
    case Enviada = 'Enviada';
    case Respondida = 'Respondida';
    case Anulada = 'Anulada';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
