<?php

namespace App\Modules\Usuarios\Enums;

enum Rol: string
{
    case Administrador = 'Administrador';
    case Gerente = 'Gerente';
    case SupervisorProduccion = 'Supervisor de Producción';
    case EncargadoStock = 'Encargado de Stock';
    case EncargadoCompras = 'Encargado de Compras';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
