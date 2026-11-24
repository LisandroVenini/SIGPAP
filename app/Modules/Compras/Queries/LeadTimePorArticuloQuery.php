<?php

namespace App\Modules\Compras\Queries;

use App\Modules\Compras\Models\DesempenoProveedor;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class LeadTimePorArticuloQuery
{
    /**
     * Statistical lead time per supplier, per quoted article and globally.
     *
     * `desempenos_de_proveedor` stores one row per order with the real delivery
     * days, so a median per supplier is meaningful. The article lead time is the
     * median of the suppliers that quoted it recently; when that is unknown the
     * global median is used.
     *
     * @return array{
     *     por_articulo: array<int, array{lead_time_dias: float|null, proveedor_ids: array<int, int>}>,
     *     global: float|null,
     *     por_proveedor: array<int, array{lead_time_dias: float, entregas: int}>,
     * }
     */
    public function resumen(?CarbonInterface $desde = null): array
    {
        $desde = ($desde ?? now()->subMonths(12))->startOfDay();

        $desempenos = DesempenoProveedor::query()
            ->where('fecha_registro', '>=', $desde->toDateString())
            ->get(['proveedor_id', 'tiempo_entrega_real']);

        $porProveedor = $desempenos
            ->groupBy(fn (DesempenoProveedor $entrega): int => (int) $entrega->proveedor_id)
            ->map(fn ($grupo): array => [
                'lead_time_dias' => $this->mediana($grupo->pluck('tiempo_entrega_real')->all()),
                'entregas' => $grupo->count(),
            ])
            ->all();

        $global = $this->mediana($desempenos->pluck('tiempo_entrega_real')->all());

        $cotizaciones = DB::table('lineas_de_cotizacion')
            ->join('cotizaciones', 'cotizaciones.id', '=', 'lineas_de_cotizacion.cotizacion_id')
            ->where('cotizaciones.fecha', '>=', $desde->toDateString())
            ->select('lineas_de_cotizacion.articulo_id', 'cotizaciones.proveedor_id')
            ->distinct()
            ->get();

        $porArticulo = [];

        foreach ($cotizaciones->groupBy('articulo_id') as $articuloId => $filas) {
            $proveedorIds = $filas->pluck('proveedor_id')->map(fn ($id): int => (int) $id)->unique()->values()->all();

            $tiempos = array_values(array_filter(array_map(
                fn (int $proveedorId): ?float => $porProveedor[$proveedorId]['lead_time_dias'] ?? null,
                $proveedorIds,
            )));

            $porArticulo[(int) $articuloId] = [
                'lead_time_dias' => $this->mediana($tiempos) ?? $global,
                'proveedor_ids' => $proveedorIds,
            ];
        }

        return [
            'por_articulo' => $porArticulo,
            'global' => $global,
            'por_proveedor' => $porProveedor,
        ];
    }

    /**
     * @param  array<int, int|float|string>  $valores
     */
    private function mediana(array $valores): ?float
    {
        $limpios = [];

        foreach ($valores as $valor) {
            $numero = (float) $valor;

            if ($numero > 0) {
                $limpios[] = $numero;
            }
        }

        if ($limpios === []) {
            return null;
        }

        sort($limpios);
        $total = count($limpios);
        $medio = intdiv($total, 2);

        return $total % 2 === 0
            ? round(($limpios[$medio - 1] + $limpios[$medio]) / 2, 2)
            : round($limpios[$medio], 2);
    }
}
