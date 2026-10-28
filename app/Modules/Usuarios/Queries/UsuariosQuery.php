<?php

namespace App\Modules\Usuarios\Queries;

use App\Modules\Usuarios\Enums\EstadoUsuario;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UsuariosQuery
{
    /**
     * Paginate the user directory with its filters.
     *
     * @param  array{buscar?: string|null, rol?: string|null, estado?: string|null}  $filtros
     */
    public function listar(array $filtros = []): LengthAwarePaginator
    {
        return Usuario::query()
            ->with('roles')
            ->when($filtros['buscar'] ?? null, function ($query, string $buscar): void {
                $query->where(function ($query) use ($buscar): void {
                    $query->where('nombre', 'like', "%{$buscar}%")
                        ->orWhere('email', 'like', "%{$buscar}%")
                        ->orWhereHas('roles', fn ($query) => $query->where('name', 'like', "%{$buscar}%"));
                });
            })
            ->when($filtros['rol'] ?? null, fn ($query, string $rol) => $query->whereHas('roles', fn ($query) => $query->where('name', $rol)))
            ->when($filtros['estado'] ?? null, fn ($query, string $estado) => $query->where('estado', $estado))
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * Aggregate the user directory in a single query.
     *
     * @return array{total: int, activos: int, inhabilitados: int}
     */
    public function resumen(): array
    {
        $row = Usuario::query()
            ->selectRaw(
                'count(*) as total, sum(case when estado = ? then 1 else 0 end) as activos, sum(case when estado = ? then 1 else 0 end) as inhabilitados',
                [EstadoUsuario::Activo->value, EstadoUsuario::Inhabilitado->value],
            )
            ->first();

        return [
            'total' => (int) $row->total,
            'activos' => (int) $row->activos,
            'inhabilitados' => (int) $row->inhabilitados,
        ];
    }
}
