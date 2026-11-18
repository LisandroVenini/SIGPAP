<?php

namespace App\Shared\Alertas\Catalogo;

use App\Modules\Usuarios\Enums\Rol;
use App\Shared\Alertas\Enums\ModuloDeAlerta;
use App\Shared\Alertas\Enums\OrigenAlerta;
use App\Shared\Alertas\Enums\SeveridadAlerta;

final class TipoDeAlerta
{
    /**
     * @param  array<int, Rol>  $rolesPorDefecto
     * @param  array<int, array{clave: string, etiqueta: string, tipo: string, minimo?: float|int, maximo?: float|int, por_defecto: float|int}>  $condiciones
     * @param  array<int, string>  $variables
     */
    public function __construct(
        public readonly string $clave,
        public readonly string $etiqueta,
        public readonly string $descripcion,
        public readonly ModuloDeAlerta $modulo,
        public readonly OrigenAlerta $origen,
        public readonly SeveridadAlerta $severidadPorDefecto,
        public readonly array $rolesPorDefecto,
        public readonly array $condiciones,
        public readonly array $variables,
    ) {}
}
