<?php

namespace App\Modules\Usuarios\Services;

use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\ParametroGlobal;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

class ParametroGlobalService
{
    /**
     * List every global parameter grouped by category.
     *
     * @return Collection<string, Collection<int, ParametroGlobal>>
     */
    public function listar(): Collection
    {
        return ParametroGlobal::query()
            ->orderBy('categoria')
            ->orderBy('nombre')
            ->get()
            ->groupBy('categoria');
    }

    /**
     * Find a global parameter by its key.
     */
    public function buscar(string $clave): ?ParametroGlobal
    {
        return ParametroGlobal::where('nombre', $clave)->first();
    }

    /**
     * Read a global parameter value. Transversal read access.
     */
    public function parametro(string $clave): ?string
    {
        return ParametroGlobal::where('nombre', $clave)->value('valor');
    }

    /**
     * Create or update a global parameter. Only an Administrador may write.
     */
    public function definirParametro(
        Usuario $actor,
        string $clave,
        string $valor,
        string $categoria = 'General',
    ): ParametroGlobal {
        if (! $actor->hasRole(Rol::Administrador->value)) {
            throw new AuthorizationException('Solo un Administrador puede definir parámetros globales.');
        }

        return ParametroGlobal::updateOrCreate(
            ['nombre' => $clave],
            ['valor' => $valor, 'categoria' => $categoria],
        );
    }
}
