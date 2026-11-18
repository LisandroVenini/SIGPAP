<?php

namespace App\Shared\Alertas\Enums;

use App\Modules\Usuarios\Enums\Rol;

enum ModuloDeAlerta: string
{
    case Inventario = 'Inventario';
    case Compras = 'Compras';
    case Produccion = 'Produccion';
    case Usuarios = 'Usuarios';

    public function permiso(): string
    {
        return match ($this) {
            self::Inventario => 'inventario.gestionar',
            self::Compras => 'compras.gestionar',
            self::Produccion => 'produccion.gestionar',
            self::Usuarios => 'usuarios.gestionar',
        };
    }

    /**
     * @return array<int, Rol>
     */
    public function rolesDelArea(): array
    {
        return match ($this) {
            self::Inventario => [Rol::EncargadoStock],
            self::Compras => [Rol::EncargadoCompras],
            self::Produccion => [Rol::SupervisorProduccion],
            self::Usuarios => [],
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
