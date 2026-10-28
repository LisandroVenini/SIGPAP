<?php

namespace App\Modules\Usuarios\Policies;

use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\ParametroGlobalService;

class ParametroGlobalPolicy
{
    public function __construct(private readonly ParametroGlobalService $parametros) {}

    public function viewAny(Usuario $actor): bool
    {
        return $this->parametros->puedeVer($actor);
    }

    public function update(Usuario $actor): bool
    {
        return $this->parametros->puedeEditar($actor);
    }
}
