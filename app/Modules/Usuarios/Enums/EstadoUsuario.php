<?php

namespace App\Modules\Usuarios\Enums;

enum EstadoUsuario: string
{
    case Activo = 'Activo';
    case Inhabilitado = 'Inhabilitado';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
