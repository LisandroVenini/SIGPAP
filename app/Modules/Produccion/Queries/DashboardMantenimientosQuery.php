<?php

namespace App\Modules\Produccion\Queries;

use App\Modules\Produccion\Enums\EstadoMaquinaria;
use App\Modules\Produccion\Models\Maquinaria;
use App\Modules\Produccion\Models\PlanDeMantenimiento;

class DashboardMantenimientosQuery
{
    /**
     * Maintenance indicators for the dashboard.
     *
     * @return array{maquinas_bloqueadas: int, planes: int}
     */
    public function resumen(): array
    {
        return [
            'maquinas_bloqueadas' => Maquinaria::query()
                ->where('estado_operativo', EstadoMaquinaria::Bloqueada->value)
                ->count(),
            'planes' => PlanDeMantenimiento::query()->count(),
        ];
    }

    /**
     * Blocked machines ordered by accumulated wear, most worn first.
     *
     * @return array<int, array{id: int, nombre: string, desgaste: float}>
     */
    public function bloqueadas(int $limite): array
    {
        return Maquinaria::query()
            ->where('estado_operativo', EstadoMaquinaria::Bloqueada->value)
            ->orderByDesc('desgaste_acumulado')
            ->orderBy('id')
            ->limit($limite)
            ->get(['id', 'nombre', 'desgaste_acumulado'])
            ->map(fn (Maquinaria $maquinaria): array => [
                'id' => $maquinaria->id,
                'nombre' => $maquinaria->nombre,
                'desgaste' => (float) $maquinaria->desgaste_acumulado,
            ])
            ->all();
    }
}
