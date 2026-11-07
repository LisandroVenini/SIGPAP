<?php

namespace App\Modules\Produccion\Queries;

use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use Illuminate\Support\Collection;

class CronogramaQuery
{
    /**
     * The orders that still occupy the production schedule.
     *
     * @return Collection<int, OrdenDeProduccion>
     */
    public function ordenesActivas(): Collection
    {
        return OrdenDeProduccion::query()
            ->whereIn('estado', [
                EstadoOrdenProduccion::Pendiente->value,
                EstadoOrdenProduccion::PendientePorInsumos->value,
                EstadoOrdenProduccion::Asignada->value,
                EstadoOrdenProduccion::EnCurso->value,
                EstadoOrdenProduccion::Pospuesta->value,
            ])
            ->orderBy('prioridad_actual')
            ->orderBy('fecha_entrega')
            ->orderBy('id')
            ->get();
    }
}
