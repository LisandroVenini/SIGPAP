<?php

namespace App\Modules\Produccion\Queries;

use App\Modules\Produccion\Models\ControlDeCalidad;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use Illuminate\Support\Facades\DB;

class CalidadQuery
{
    public function __construct(
        private readonly PartesDiariosQuery $partesDiarios,
    ) {}

    /**
     * Return the approved quantity that can be used to close each order.
     *
     * The calculation follows these rules, in order:
     * 1. If an order-level control exists (parte_diario_id is null), its approved
     *    quantity is used.
     * 2. Otherwise, if there are parte-level controls for the last route stage,
     *    approved = conformado_total_de_la_ultima_etapa - sum(rechazadas).
     * 3. If neither exists, the result is null.
     *
     * @param  array<int, int>  $ordenIds
     * @param  array<int, int>  $ultimaEtapaPorArticulo
     * @return array<int, array{aprobado: float|null, tiene_control: bool}>
     */
    public function aprobadoParaCierre(array $ordenIds, array $ultimaEtapaPorArticulo): array
    {
        $resultado = [];

        foreach ($ordenIds as $ordenId) {
            $resultado[(int) $ordenId] = [
                'aprobado' => null,
                'tiene_control' => false,
            ];
        }

        if ($ordenIds === []) {
            return $resultado;
        }

        $controlesDeOrden = $this->controlesDeOrdenMasRecientes($ordenIds);
        $conformadoPorOrden = $this->partesDiarios->conformadoPorOrdenes($ordenIds);
        $rechazadasPorOrden = $this->rechazadasPorEtapaDeOrdenes($ordenIds, $ultimaEtapaPorArticulo);

        $articulosPorOrden = OrdenDeProduccion::query()
            ->whereIn('id', $ordenIds)
            ->pluck('articulo_id', 'id')
            ->mapWithKeys(fn (int|string $articuloId, int|string $ordenId): array => [
                (int) $ordenId => (int) $articuloId,
            ])
            ->all();

        foreach ($ordenIds as $ordenId) {
            $ordenId = (int) $ordenId;

            if (array_key_exists($ordenId, $controlesDeOrden)) {
                $resultado[$ordenId] = [
                    'aprobado' => $controlesDeOrden[$ordenId],
                    'tiene_control' => true,
                ];

                continue;
            }

            $articuloId = $articulosPorOrden[$ordenId] ?? null;
            $ultimaEtapaId = $articuloId !== null ? ($ultimaEtapaPorArticulo[$articuloId] ?? null) : null;

            if ($ultimaEtapaId === null) {
                continue;
            }

            if (! array_key_exists($ordenId, $rechazadasPorOrden) || $rechazadasPorOrden[$ordenId] === null) {
                continue;
            }

            $conformado = $conformadoPorOrden[$ordenId][$ultimaEtapaId] ?? 0.0;
            $rechazadas = $rechazadasPorOrden[$ordenId];

            $resultado[$ordenId] = [
                'aprobado' => round($conformado - $rechazadas, 2),
                'tiene_control' => true,
            ];
        }

        return $resultado;
    }

    /**
     * Sum rejected quantities for each order considering only parte-level controls
     * that belong to the product's last route stage.
     *
     * Orders without applicable controls keep a null value.
     *
     * @param  array<int, int>  $ordenIds
     * @param  array<int, int>  $ultimaEtapaPorArticulo
     * @return array<int, float|null>
     */
    public function rechazadasPorEtapaDeOrdenes(array $ordenIds, array $ultimaEtapaPorArticulo): array
    {
        $resultado = array_fill_keys(array_map('intval', $ordenIds), null);

        if ($ordenIds === [] || $ultimaEtapaPorArticulo === []) {
            return $resultado;
        }

        $pares = [];
        foreach ($ultimaEtapaPorArticulo as $articuloId => $etapaId) {
            $pares[] = '('.(int) $articuloId.','.(int) $etapaId.')';
        }

        $filas = DB::table('controles_de_calidad')
            ->join('partes_diarios', 'partes_diarios.id', '=', 'controles_de_calidad.parte_diario_id')
            ->join('ordenes_de_produccion', 'ordenes_de_produccion.id', '=', 'controles_de_calidad.orden_de_produccion_id')
            ->whereIn('controles_de_calidad.orden_de_produccion_id', $ordenIds)
            ->whereNotNull('controles_de_calidad.parte_diario_id')
            ->whereRaw('(ordenes_de_produccion.articulo_id, partes_diarios.etapa_id) in ('.implode(',', $pares).')')
            ->selectRaw('controles_de_calidad.orden_de_produccion_id, sum(controles_de_calidad.cantidad_rechazada) as total')
            ->groupBy('controles_de_calidad.orden_de_produccion_id')
            ->get();

        foreach ($filas as $fila) {
            $resultado[(int) $fila->orden_de_produccion_id] = (float) $fila->total;
        }

        return $resultado;
    }

    /**
     * @param  array<int, int>  $ordenIds
     * @return array<int, float>
     */
    private function controlesDeOrdenMasRecientes(array $ordenIds): array
    {
        $filas = ControlDeCalidad::query()
            ->select('controles_de_calidad.orden_de_produccion_id', 'controles_de_calidad.cantidad_aprobada')
            ->selectRaw('row_number() over (partition by controles_de_calidad.orden_de_produccion_id order by controles_de_calidad.fecha desc, controles_de_calidad.id desc) as fila')
            ->whereIn('controles_de_calidad.orden_de_produccion_id', $ordenIds)
            ->whereNull('controles_de_calidad.parte_diario_id')
            ->get()
            ->where('fila', 1);

        $resultado = [];
        foreach ($filas as $fila) {
            $resultado[(int) $fila->orden_de_produccion_id] = (float) $fila->cantidad_aprobada;
        }

        return $resultado;
    }
}
