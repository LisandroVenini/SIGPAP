<?php

namespace App\Modules\Compras\Enums;

enum TipoIncidencia: string
{
    case Retraso = 'Retraso';
    case Faltante = 'Faltante';
    case Rotura = 'Rotura';
    case AnulacionRecepcion = 'Anulación de recepción';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
