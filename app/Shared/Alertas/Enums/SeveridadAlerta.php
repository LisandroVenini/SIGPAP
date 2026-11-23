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

    /**
     * @return array<int, array{valor: string, etiqueta: string}>
     */
    public static function opciones(): array
    {
        return array_map(
            fn (self $severidad): array => [
                'valor' => $severidad->value,
                'etiqueta' => $severidad->etiqueta(),
            ],
            self::cases(),
        );
    }
}
