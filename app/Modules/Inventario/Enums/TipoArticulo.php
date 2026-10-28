<?php

namespace App\Modules\Inventario\Enums;

enum TipoArticulo: string
{
    case MateriaPrima = 'Materia Prima';
    case InsumoConsumible = 'Insumo Consumible';
    case ProductoTerminado = 'Producto Terminado';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
