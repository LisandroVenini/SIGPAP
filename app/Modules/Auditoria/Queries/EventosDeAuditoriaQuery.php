<?php

namespace App\Modules\Auditoria\Queries;

use App\Modules\Auditoria\Models\EventoDeAuditoria;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class EventosDeAuditoriaQuery
{
    /**
     * Paginate the audit trail with its filters.
     *
     * @param  array{usuario_id?: int|string|null, accion?: string|null, direccion_ip?: string|null, fecha_desde?: string|null, fecha_hasta?: string|null}  $filtros
     */
    public function listar(array $filtros = []): LengthAwarePaginator
    {
        return EventoDeAuditoria::query()
            ->when($filtros['usuario_id'] ?? null, fn (Builder $query, int|string $usuarioId) => $query->where('usuario_id', $usuarioId))
            ->when(
                $filtros['accion'] ?? null,
                fn (Builder $query, string $accion) => $query->where('accion', 'ilike', '%'.addcslashes($accion, '%_\\').'%'),
            )
            ->when(
                $filtros['direccion_ip'] ?? null,
                fn (Builder $query, string $ip) => $query->where('direccion_ip', 'like', '%'.addcslashes($ip, '%_\\').'%'),
            )
            ->when(
                $filtros['fecha_desde'] ?? null,
                fn (Builder $query, string $fecha) => $query->whereDate('fecha_hora', '>=', $fecha),
            )
            ->when(
                $filtros['fecha_hasta'] ?? null,
                fn (Builder $query, string $fecha) => $query->whereDate('fecha_hora', '<=', $fecha),
            )
            ->orderByDesc('fecha_hora')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (EventoDeAuditoria $evento): array => [
                'id' => $evento->id,
                'fecha_hora' => $evento->fecha_hora->format('Y-m-d H:i:s'),
                'usuario_id' => $evento->usuario_id,
                'accion' => $evento->accion,
                'direccion_ip' => $evento->direccion_ip,
                'auditable_type' => $evento->auditable_type,
                'auditable_id' => $evento->auditable_id,
                'estado_anterior' => $evento->estado_anterior,
                'estado_nuevo' => $evento->estado_nuevo,
            ]);
    }

    /**
     * Ids of every user that appears in the audit trail.
     *
     * @return array<int, int>
     */
    public function usuariosRegistrados(): array
    {
        return EventoDeAuditoria::query()
            ->distinct()
            ->pluck('usuario_id')
            ->map(fn (int|string $id): int => (int) $id)
            ->all();
    }

    /**
     * Distinct actions ordered alphabetically.
     *
     * @return array<int, string>
     */
    public function accionesRegistradas(): array
    {
        return EventoDeAuditoria::query()
            ->distinct()
            ->orderBy('accion')
            ->pluck('accion')
            ->all();
    }
}
