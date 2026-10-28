<?php

namespace App\Modules\Inventario\Enums;

enum MotivoAjuste: string
{
    case Rotura = 'Rotura';
    case Auditoria = 'Auditoría';
    case Merma = 'Merma';
    case Otro = 'Otro';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
