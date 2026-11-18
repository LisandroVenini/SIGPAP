<?php

namespace App\Shared\Alertas\Enums;

enum OrigenAlerta: string
{
    case Condicion = 'condicion';
    case Evento = 'evento';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
