<?php

namespace App\Modules\Inventario\Enums;

enum EstadoArticulo: string
{
    case Activo = 'Activo';
    case Inactivo = 'Inactivo';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
