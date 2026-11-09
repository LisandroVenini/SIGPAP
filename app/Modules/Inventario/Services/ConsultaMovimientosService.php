<?php

namespace App\Modules\Inventario\Services;

use App\Modules\Inventario\Queries\ReporteMovimientosQuery;

class ConsultaMovimientosService
{
    public function __construct(private readonly ReporteMovimientosQuery $query) {}

    /**
     * Stock movements inside the requested date range, already formatted for reports.
     *
     * @return array<int, array<string, mixed>>
     */
    public function reportePorFecha(string $desde, string $hasta): array
    {
        return $this->query->filas($desde, $hasta)->values()->all();
    }
}
