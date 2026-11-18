<?php

namespace App\Shared\Alertas\Queries;

use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use App\Shared\Alertas\Models\Alerta;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AlertaQuery
{
    /**
     * @return Builder<Alerta>
     */
    public function visiblesPara(Usuario $usuario): Builder
    {
        $query = Alerta::query()->whereNull('archivada_en');

        if ($usuario->hasRole(Rol::Administrador->value)) {
            return $query;
        }

        $roles = $usuario->getRoleNames()->all();

        return $query->whereHas('roles', fn (Builder $rolesQuery) => $rolesQuery->whereIn('roles.name', $roles));
    }

    /**
     * @param  array{estado?: string|null, severidad?: string|null, tipo?: string|null, no_leidas?: bool}  $filtros
     */
    public function listar(Usuario $usuario, array $filtros = []): LengthAwarePaginator
    {
        $query = $this->visiblesPara($usuario)
            ->with('roles')
            ->orderByRaw("case severidad when 'Critica' then 0 when 'Atencion' then 1 else 2 end")
            ->orderByDesc('fecha');

        if (($filtros['estado'] ?? null) === 'abiertas') {
            $query->whereNull('resuelta_en');
        }

        if (($filtros['severidad'] ?? null) !== null && $filtros['severidad'] !== '') {
            $query->where('severidad', $filtros['severidad']);
        }

        if (($filtros['tipo'] ?? null) !== null && $filtros['tipo'] !== '') {
            $query->where('tipo', $filtros['tipo']);
        }

        if (($filtros['no_leidas'] ?? false) === true) {
            $query->whereDoesntHave('lecturas', fn (Builder $lecturas) => $lecturas->where('usuario_id', $usuario->id));
        }

        return $query->paginate(20)->withQueryString();
    }

    public function noLeidas(Usuario $usuario): int
    {
        return $this->visiblesPara($usuario)
            ->whereDoesntHave('lecturas', fn (Builder $lecturas) => $lecturas->where('usuario_id', $usuario->id))
            ->count();
    }

    /**
     * @return Collection<int, Alerta>
     */
    public function ultimas(Usuario $usuario, int $limite = 5): Collection
    {
        return $this->visiblesPara($usuario)
            ->orderByDesc('fecha')
            ->limit($limite)
            ->get();
    }
}
