<?php

namespace App\Modules\Produccion\Queries;

class AvancePorEtapasQuery
{
    public function __construct(
        private readonly HojaDeRutaQuery $hojaDeRuta,
        private readonly PartesDiariosQuery $partes,
    ) {}

    /**
     * Build the per-stage progress of several orders.
     *
     * The remaining quantity of a stage follows the same rule the daily-part
     * ceiling applies: required units plus rejected units minus already
     * conformed units. It never goes below zero.
     *
     * @param  array<int, int>  $articuloIdPorOrden
     * @param  array<int, float>  $requeridaPorOrden
     * @return array<int, array<int, array{etapa_id:int, nombre:string, producida:float, rechazadas:float, faltante:float}>>
     */
    public function porOrdenes(array $articuloIdPorOrden, array $requeridaPorOrden): array
    {
        if ($articuloIdPorOrden === []) {
            return [];
        }

        $ordenIds = array_keys($articuloIdPorOrden);
        $etapasPorArticulo = $this->hojaDeRuta->etapasConIdPorArticulo(
            array_values(array_unique($articuloIdPorOrden)),
        );
        $conformadoPorOrden = $this->partes->conformadoPorOrdenes($ordenIds);
        $rechazadasPorOrden = $this->partes->rechazadasPorOrdenes($ordenIds);

        $resultado = [];

        foreach ($articuloIdPorOrden as $ordenId => $articuloId) {
            $conformadoPorEtapa = $conformadoPorOrden[$ordenId] ?? [];
            $rechazadasPorEtapa = $rechazadasPorOrden[$ordenId] ?? [];
            $requerida = (float) ($requeridaPorOrden[$ordenId] ?? 0.0);

            $resultado[$ordenId] = array_map(
                function (array $etapa) use ($conformadoPorEtapa, $rechazadasPorEtapa, $requerida): array {
                    $producida = (float) ($conformadoPorEtapa[$etapa['id']] ?? 0.0);
                    $rechazadas = (float) ($rechazadasPorEtapa[$etapa['id']] ?? 0.0);

                    return [
                        'etapa_id' => $etapa['id'],
                        'nombre' => $etapa['nombre'],
                        'producida' => round($producida, 2),
                        'rechazadas' => round($rechazadas, 2),
                        'faltante' => round(max(0.0, $requerida + $rechazadas - $producida), 2),
                    ];
                },
                $etapasPorArticulo[$articuloId] ?? [],
            );
        }

        return $resultado;
    }
}
