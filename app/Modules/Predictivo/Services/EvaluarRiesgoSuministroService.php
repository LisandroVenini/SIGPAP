<?php

namespace App\Modules\Predictivo\Services;

use App\Modules\Compras\Services\ConsultaProveedoresService;
use App\Modules\Inventario\Services\ConsultaInventarioService;
use App\Modules\Usuarios\Services\ParametroGlobalService;

/**
 * CU-PRED-03: evaluate the supply risk.
 *
 * The module prepares the inputs from the owner modules, delegates the
 * statistical calculation to the engine and enriches the result for the
 * callers. It never creates drafts nor alerts: those belong to the origin
 * modules.
 */
class EvaluarRiesgoSuministroService
{
    public function __construct(
        private readonly MLEngine $engine,
        private readonly ConsultaInventarioService $inventario,
        private readonly ConsultaProveedoresService $proveedores,
        private readonly ParametroGlobalService $parametros,
    ) {}

    /**
     * @return array{fecha_base: string, criticidad: array<int, array<string, mixed>>, evaluados: int}
     */
    public function evaluar(?string $fechaBase = null): array
    {
        $fecha = $fechaBase ?? now()->toDateString();
        $inventario = $this->inventario->datosDeInventario();
        $proveedores = $this->proveedores->datosDeProveedores();

        $insumos = collect($inventario['articulos'])
            ->map(fn (array $articulo): array => [
                'articulo_id' => $articulo['id'],
                'stock_fisico' => $articulo['stock_fisico'],
                'comprometido' => $articulo['comprometido'],
                'consumo_diario' => $articulo['consumo_diario'],
                'lead_time_dias' => $proveedores['por_articulo'][$articulo['id']]['lead_time_dias']
                    ?? $proveedores['global']
                    ?? 0.0,
                'stock_minimo' => $articulo['stock_minimo'],
                'stock_maximo' => $articulo['stock_maximo'],
            ])
            ->all();

        $resultado = $this->engine->evaluarRiesgoSuministro([
            'fecha_base' => $fecha,
            'dias_aviso' => $this->diasAviso(),
            'insumos' => $insumos,
        ]);

        $descripciones = collect($inventario['articulos'])
            ->keyBy('id')
            ->map(fn (array $articulo): string => $articulo['descripcion'])
            ->all();

        return [
            'fecha_base' => $fecha,
            'criticidad' => collect($resultado['criticidad'])
                ->map(fn (array $fila): array => [
                    ...$fila,
                    'descripcion' => $descripciones[$fila['articulo_id']] ?? null,
                ])
                ->values()
                ->all(),
            'evaluados' => (int) $resultado['evaluados'],
        ];
    }

    private function diasAviso(): int
    {
        $valor = $this->parametros->parametro('predictivo_dias_aviso_riesgo');

        return max(0, $valor !== null ? (int) $valor : 7);
    }
}
