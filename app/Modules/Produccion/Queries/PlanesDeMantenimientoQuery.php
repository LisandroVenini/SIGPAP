<?php

namespace App\Modules\Produccion\Queries;

use App\Modules\Produccion\Enums\EstadoMaquinaria;
use App\Modules\Produccion\Models\PlanDeMantenimiento;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PlanesDeMantenimientoQuery
{
    /**
     * @param  array{buscar?: string|null}  $filtros
     */
    public function listar(array $filtros = []): LengthAwarePaginator
    {
        return PlanDeMantenimiento::query()
            ->join('maquinarias', 'maquinarias.id', '=', 'planes_de_mantenimiento.maquinaria_id')
            ->select([
                'planes_de_mantenimiento.id',
                'planes_de_mantenimiento.maquinaria_id',
                'planes_de_mantenimiento.umbral_uso',
                'planes_de_mantenimiento.descripcion',
                'maquinarias.nombre as maquinaria',
                'maquinarias.estado_operativo',
            ])
            ->when($filtros['buscar'] ?? null, fn ($query, string $buscar) => $query->buscar($buscar))
            ->orderBy('maquinarias.nombre')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (PlanDeMantenimiento $plan) => [
                'id' => $plan->id,
                'maquinaria_id' => $plan->maquinaria_id,
                'maquinaria' => $plan->maquinaria,
                'umbral_uso' => $plan->umbral_uso,
                'descripcion' => $plan->descripcion,
                'maquina_bloqueada' => $plan->estado_operativo === EstadoMaquinaria::Bloqueada->value,
            ]);
    }
}
