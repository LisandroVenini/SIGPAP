<?php

namespace App\Modules\Compras\Services;

use App\Modules\Compras\Queries\LeadTimePorArticuloQuery;

/**
 * Read-only public contract of the Compras module for predictive supply risk.
 */
class ConsultaProveedoresService
{
    public function __construct(private readonly LeadTimePorArticuloQuery $leadTimes) {}

    /**
     * @return array{
     *     por_articulo: array<int, array{lead_time_dias: float|null, proveedor_ids: array<int, int>}>,
     *     global: float|null,
     *     por_proveedor: array<int, array{lead_time_dias: float, entregas: int}>,
     *     generado_en: string,
     * }
     */
    public function datosDeProveedores(): array
    {
        return [
            ...$this->leadTimes->resumen(),
            'generado_en' => now()->toDateTimeString(),
        ];
    }
}
