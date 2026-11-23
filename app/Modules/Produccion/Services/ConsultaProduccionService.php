<?php

namespace App\Modules\Produccion\Services;

use App\Modules\Produccion\Models\Maquinaria;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Queries\CronogramaQuery;
use App\Modules\Produccion\Queries\HojaDeRutaQuery;
use App\Modules\Produccion\Queries\PartesDiariosQuery;
use Illuminate\Support\Collection;

/**
 * Read-only public contract of the Producción module.
 *
 * Other modules (Predictivo, Reportes) consume production data through this
 * service and never touch its models. The payload is plain arrays so the
 * caller stays decoupled from Eloquent.
 */
class ConsultaProduccionService
{
    public function __construct(
        private readonly CronogramaQuery $cronograma,
        private readonly HojaDeRutaQuery $hojaDeRuta,
        private readonly PartesDiariosQuery $partesDiarios,
    ) {}

    /**
     * Production dataset consumed by the predictive engine.
     *
     * - `ordenes`: the orders that still occupy the schedule.
     * - `rutas`: each product's route sheet with its standard minutes per unit.
     * - `partes`: the daily parts registered for those orders, grouped by order.
     * - `maquinaria`: machines with their accumulated wear.
     *
     * @return array{
     *     ordenes: array<int, array{id:int, articulo_id:int, estado:string, cantidad_requerida:float, cantidad_producida:float, fecha_entrega:string|null, fecha_inicio:string|null, fecha_fin:string|null, prioridad_actual:int}>,
     *     rutas: array<int, array<int, array{etapa_id:int, etapa:string, tiempo_por_unidad:float}>>,
     *     partes: array<int, array<int, array{etapa_id:int, etapa:string, fecha:string, cantidad_conformada:float, maquinaria:string|null}>>,
     *     maquinaria: array<int, array{id:int, nombre:string, tipo:string|null, estado_operativo:string|null, desgaste_acumulado:float}>,
     *     generado_en: string,
     * }
     */
    public function datosDeProduccion(): array
    {
        $ordenes = $this->cronograma->ordenesActivas();
        $articuloIds = $ordenes->pluck('articulo_id')->unique()->values()->all();
        $ordenIds = $ordenes->pluck('id')->all();

        return [
            'ordenes' => $ordenes
                ->map(fn (OrdenDeProduccion $orden): array => [
                    'id' => $orden->id,
                    'articulo_id' => (int) $orden->articulo_id,
                    'estado' => (string) $orden->estado,
                    'cantidad_requerida' => (float) $orden->cantidad_requerida,
                    'cantidad_producida' => (float) $orden->cantidad_producida,
                    'fecha_entrega' => $orden->fecha_entrega?->toDateString(),
                    'fecha_inicio' => $orden->fecha_inicio?->toDateString(),
                    'fecha_fin' => $orden->fecha_fin?->toDateString(),
                    'prioridad_actual' => (int) $orden->prioridad_actual,
                ])
                ->values()
                ->all(),
            'rutas' => $this->hojaDeRuta->rutaPorArticulo($articuloIds),
            'partes' => $this->partesPorOrden($ordenIds),
            'maquinaria' => Maquinaria::query()
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'tipo', 'estado_operativo', 'desgaste_acumulado'])
                ->map(fn (Maquinaria $maquinaria): array => [
                    'id' => $maquinaria->id,
                    'nombre' => $maquinaria->nombre,
                    'tipo' => $maquinaria->tipo,
                    'estado_operativo' => $maquinaria->estado_operativo,
                    'desgaste_acumulado' => (float) $maquinaria->desgaste_acumulado,
                ])
                ->values()
                ->all(),
            'generado_en' => now()->toDateTimeString(),
        ];
    }

    /**
     * @param  array<int, int>  $ordenIds
     * @return array<int, array<int, array{etapa_id:int, etapa:string, fecha:string, cantidad_conformada:float, maquinaria:string|null}>>
     */
    private function partesPorOrden(array $ordenIds): array
    {
        return collect($this->partesDiarios->porOrdenes($ordenIds))
            ->map(fn (Collection $filas): array => $filas
                ->map(fn (array $fila): array => [
                    'etapa_id' => $fila['etapa_id'],
                    'etapa' => $fila['etapa'],
                    'fecha' => $fila['fecha'],
                    'cantidad_conformada' => $fila['cantidad'],
                    'maquinaria' => $fila['maquinaria'],
                ])
                ->values()
                ->all())
            ->all();
    }
}
