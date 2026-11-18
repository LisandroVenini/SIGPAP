<?php

namespace App\Modules\Produccion\Services;

use App\Modules\Inventario\Services\GestionArticulosService;
use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use App\Modules\Produccion\Models\Maquinaria;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Models\ParteDiario;
use App\Shared\Alertas\Services\AlertaService;

class VigilanteDeAlertasService
{
    public function __construct(
        private readonly AlertaService $alertas,
        private readonly GestionArticulosService $articulos,
    ) {}

    public function evaluar(): int
    {
        return $this->evaluarOrdenes() + $this->evaluarMantenimientos();
    }

    private function evaluarOrdenes(): int
    {
        $atrasadas = [];
        $porVencer = [];
        $sinInsumos = [];

        $conformado = ParteDiario::query()
            ->selectRaw('orden_de_produccion_id, sum(cantidad_conformada) as total')
            ->groupBy('orden_de_produccion_id')
            ->pluck('total', 'orden_de_produccion_id');

        $ordenes = OrdenDeProduccion::query()
            ->whereNotIn('estado', [EstadoOrdenProduccion::Cerrada->value, EstadoOrdenProduccion::Anulada->value])
            ->get();

        $resumenes = $this->articulos->resumenesDeArticulos(
            $ordenes->pluck('articulo_id')->unique()->values()->all(),
        );

        $ordenes
            ->each(function (OrdenDeProduccion $orden) use (&$atrasadas, &$porVencer, &$sinInsumos, $conformado, $resumenes): void {
                $dias = (int) round(today()->diffInDays($orden->fecha_entrega, false));
                $requerida = (float) $orden->cantidad_requerida;
                $producida = (float) ($conformado[$orden->id] ?? 0);
                $avance = $requerida > 0 ? min(100, round($producida / $requerida * 100, 2)) : 0.0;
                $referencia = 'orden:'.$orden->id;
                $contexto = [
                    'orden' => $orden->id,
                    'producto' => $resumenes[$orden->articulo_id]['descripcion'] ?? 'Orden #'.$orden->id,
                    'fecha_entrega' => $orden->fecha_entrega?->toDateString() ?? '—',
                    'dias' => $dias,
                    'avance' => $avance,
                    'orden_de_produccion_id' => $orden->id,
                ];

                if ($dias < 0) {
                    $atrasadas[$referencia] = $contexto;
                } else {
                    $porVencer[$referencia] = $contexto;
                }

                if ($orden->estado === EstadoOrdenProduccion::PendientePorInsumos->value) {
                    $sinInsumos[$referencia] = [
                        'orden' => $orden->id,
                        'producto' => $contexto['producto'],
                        'insumos' => 'pendientes',
                        'orden_de_produccion_id' => $orden->id,
                    ];
                }
            });

        return $this->alertas->sincronizarPorTipo('orden_atrasada', $atrasadas)
            + $this->alertas->sincronizarPorTipo('orden_por_vencer', $porVencer)
            + $this->alertas->sincronizarPorTipo('orden_sin_insumos', $sinInsumos);
    }

    private function evaluarMantenimientos(): int
    {
        $contextos = [];

        Maquinaria::query()
            ->with('planesDeMantenimiento')
            ->get()
            ->each(function (Maquinaria $maquinaria) use (&$contextos): void {
                $plan = $maquinaria->planesDeMantenimiento->first();

                if ($plan === null || (float) $maquinaria->desgaste_acumulado < (float) $plan->umbral_uso) {
                    return;
                }

                $contextos['maquinaria:'.$maquinaria->id] = [
                    'maquinaria' => $maquinaria->nombre,
                    'desgaste' => (float) $maquinaria->desgaste_acumulado,
                    'umbral' => (float) $plan->umbral_uso,
                    'maquinaria_id' => $maquinaria->id,
                ];
            });

        return $this->alertas->sincronizarPorTipo('mantenimiento_vencido', $contextos);
    }
}
