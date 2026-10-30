<?php

namespace App\Modules\Produccion\Enums;

enum ModoDeAltaOrden: string
{
    case SoloDisponibles = 'solo_disponibles';
    case Todas = 'todas';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
