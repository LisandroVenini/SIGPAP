<?php

namespace App\Modules\Usuarios\Services;

use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Support\Str;

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
     * Resolve a user by its email.
     */
    /**
     * Technical actor for automated decisions (CAM-001).
     *
     * It has no role and no access on purpose: it only exists so the audit
     * trail keeps a non-null actor for system processes.
     */
    public function usuarioSistema(): Usuario
    {
        return Usuario::query()->firstOrCreate(
            ['email' => 'sistema@sigpap.local'],
            ['nombre' => 'Sistema', 'clave_acceso' => Str::password(32)],
        );
    }

    public function buscarPorEmail(string $email): ?Usuario
    {
        return Usuario::query()->where('email', $email)->first();
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
