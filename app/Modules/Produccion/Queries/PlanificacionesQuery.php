<?php

namespace App\Modules\Produccion\Queries;

use App\Modules\Produccion\Models\PlanificacionDeProduccion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PlanificacionesQuery
{
    /**
     * @param  array{buscar?: string|null}  $filtros
     */
    public function listar(array $filtros = []): LengthAwarePaginator
    {
        return PlanificacionDeProduccion::query()
            ->withCount('ordenesDeProduccion')
            ->when($filtros['buscar'] ?? null, fn ($query, string $buscar) => $query->buscar($buscar))
            ->orderByDesc('fecha_inicio')
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (PlanificacionDeProduccion $planificacion) => [
                'id' => $planificacion->id,
                'nombre' => $planificacion->nombre,
                'fecha_inicio' => $planificacion->fecha_inicio?->toDateString(),
                'fecha_fin' => $planificacion->fecha_fin?->toDateString(),
                'observaciones' => $planificacion->observaciones,
                'ordenes' => $planificacion->ordenes_de_produccion_count,
            ]);
    }
}
