<?php

namespace App\Modules\Inventario\Enums;

enum OrigenArticulo: string
{
    case Fabricado = 'Fabricado';
    case Tercero = 'Tercero';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
