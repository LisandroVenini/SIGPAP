<?php

namespace App\Modules\Produccion\Enums;

enum EstadoMaquinaria: string
{
    case Disponible = 'Disponible';
    case EnMantenimiento = 'En Mantenimiento';
    case Bloqueada = 'Bloqueada';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
