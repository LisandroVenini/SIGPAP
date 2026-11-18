<?php

namespace App\Shared\Alertas\Enums;

enum SeveridadAlerta: string
{
    case Info = 'Info';
    case Atencion = 'Atencion';
    case Critica = 'Critica';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Info => 'Info',
            self::Atencion => 'Atención',
            self::Critica => 'Crítica',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
