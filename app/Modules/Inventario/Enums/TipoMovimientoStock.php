<?php

namespace App\Modules\Inventario\Enums;

enum TipoMovimientoStock: string
{
    case Ingreso = 'Ingreso';
    case Consumo = 'Consumo';
    case Salida = 'Salida';
    case Ajuste = 'Ajuste';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
