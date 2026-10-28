<?php

namespace App\Modules\Inventario\Enums;

enum EstadoReserva: string
{
    case Vigente = 'Vigente';
    case Consumida = 'Consumida';
    case Liberada = 'Liberada';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
