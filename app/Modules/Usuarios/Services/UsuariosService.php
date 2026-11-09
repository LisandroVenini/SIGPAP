<?php

namespace App\Modules\Usuarios\Services;

use App\Modules\Usuarios\Models\Usuario;

class UsuariosService
{
    /**
     * Determine whether the user holds the requested permission.
     */
    public function autorizar(Usuario $usuario, string $operacion): bool
    {
        return $usuario->can($operacion);
    }

    /**
     * Resolve several users in a single query.
     *
     * @param  array<int, int>  $ids
     * @return array<int, array{id: int, nombre: string}>
     */
    public function resumenesDeUsuarios(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));

        return Usuario::query()
            ->whereIn('id', $ids)
            ->orderBy('nombre')
            ->get(['id', 'nombre'])
            ->mapWithKeys(fn (Usuario $usuario): array => [
                $usuario->id => [
                    'id' => $usuario->id,
                    'nombre' => $usuario->nombre,
                ],
            ])
            ->all();
    }
}
