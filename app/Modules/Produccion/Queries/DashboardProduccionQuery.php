<?php

namespace App\Modules\Produccion\Queries;

use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use Illuminate\Support\Collection;

class DashboardProduccionQuery
{
    /**
     * Order states that are still open and therefore count as active.
     *
     * @var array<int, string>
     */
    private const ESTADOS_ACTIVOS = [
        EstadoOrdenProduccion::Pendiente->value,
        EstadoOrdenProduccion::PendientePorInsumos->value,
        EstadoOrdenProduccion::Asignada->value,
        EstadoOrdenProduccion::EnCurso->value,
        EstadoOrdenProduccion::Pospuesta->value,
    ];

    /**
     * Aggregate the active order counters in a single query.
     *
     * @return array{activas: int, en_curso: int, pendientes_por_insumos: int, atrasadas: int}
     */
    public function agregados(string $hoy): array
    {
        $estados = self::ESTADOS_ACTIVOS;
        $marcadores = implode(', ', array_fill(0, count($estados), '?'));

        $fila = OrdenDeProduccion::query()
            ->selectRaw("count(*) filter (where estado in ({$marcadores})) as activas", $estados)
            ->selectRaw('count(*) filter (where estado = ?) as en_curso', [EstadoOrdenProduccion::EnCurso->value])
            ->selectRaw('count(*) filter (where estado = ?) as pendientes_por_insumos', [EstadoOrdenProduccion::PendientePorInsumos->value])
            ->selectRaw(
                "count(*) filter (where estado in ({$marcadores}) and fecha_entrega < ?) as atrasadas",
                [...$estados, $hoy],
            )
            ->first();

        return [
            'activas' => (int) $fila->activas,
            'en_curso' => (int) $fila->en_curso,
            'pendientes_por_insumos' => (int) $fila->pendientes_por_insumos,
            'atrasadas' => (int) $fila->atrasadas,
        ];
    }

    /**
     * Active orders whose delivery date has not passed yet, soonest first.
     *
     * @return Collection<int, array{orden_id: int, articulo_id: int, fecha_entrega: string, estado: string}>
     */
    public function proximasEntregas(int $limite, string $hoy): Collection
    {
        return OrdenDeProduccion::query()
            ->whereIn('estado', self::ESTADOS_ACTIVOS)
            ->whereDate('fecha_entrega', '>=', $hoy)
            ->orderBy('fecha_entrega')
            ->orderBy('id')
            ->limit($limite)
            ->get(['id', 'articulo_id', 'fecha_entrega', 'estado'])
            ->map(fn (OrdenDeProduccion $orden): array => [
                'orden_id' => $orden->id,
                'articulo_id' => $orden->articulo_id,
                'fecha_entrega' => $orden->fecha_entrega->toDateString(),
                'estado' => $orden->estado,
            ]);
    }
}
