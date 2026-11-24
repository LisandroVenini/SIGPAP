<?php

namespace App\Modules\Predictivo\Services;

use App\Modules\Predictivo\Models\EscenarioDeSimulacion;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Services\GestionCronogramaService;
use Carbon\CarbonImmutable;

/**
 * CU-PRED-04: simulate an urgent order.
 *
 * The module inserts an unsaved priority-1 order into the real projection and
 * compares both schedules. It never touches production: the only write is its
 * own `EscenarioDeSimulacion`.
 */
class SimularEscenarioService
{
    public function __construct(private readonly GestionCronogramaService $cronograma) {}

    /**
     * @return array{escenario_id: int|null, viable: bool, motivo: string|null, pedido: array{articulo_id: int, articulo: string|null, cantidad: float, fecha_limite: string, fecha_fin_proyectada: string|null}, retrasos: array<int, array{orden_id: int, producto: string, fecha_entrega: string|null, dias_retraso: int}>, impacto_proyectado: float}
     */
    public function simular(int $articuloId, float $cantidad, string $fechaLimite): array
    {
        $ficticia = $this->ordenFicticia($articuloId, $cantidad, $fechaLimite);

        $base = $this->cronograma->proyectar();
        $simulada = $this->cronograma->proyectar(collect([$ficticia]));

        $filaPedido = collect($simulada['filas'])->firstWhere('orden_id', $ficticia->id);
        $finPedido = $filaPedido !== null ? $this->fechaFin($filaPedido) : null;

        if ($finPedido === null) {
            return [
                'escenario_id' => null,
                'viable' => false,
                'motivo' => 'articulo_sin_ruta',
                'pedido' => [
                    'articulo_id' => $articuloId,
                    'articulo' => $filaPedido['producto'] ?? null,
                    'cantidad' => $cantidad,
                    'fecha_limite' => $fechaLimite,
                    'fecha_fin_proyectada' => null,
                ],
                'retrasos' => [],
                'impacto_proyectado' => 0.0,
            ];
        }

        $limite = CarbonImmutable::parse($fechaLimite)->startOfDay();
        $viable = $finPedido->lessThanOrEqualTo($limite);

        $basePorOrden = collect($base['filas'])->keyBy('orden_id');
        $retrasos = [];
        $impacto = 0.0;

        foreach ($simulada['filas'] as $fila) {
            if ($fila['orden_id'] === $ficticia->id) {
                continue;
            }

            $filaBase = $basePorOrden->get($fila['orden_id']);
            $finBase = $filaBase !== null ? $this->fechaFin($filaBase) : null;
            $finSimulado = $this->fechaFin($fila);

            if ($finBase === null || $finSimulado === null) {
                continue;
            }

            $dias = (int) ceil(
                ($finSimulado->startOfDay()->getTimestamp() - $finBase->startOfDay()->getTimestamp()) / 86_400,
            );

            if ($dias > 0) {
                $retrasos[] = [
                    'orden_id' => (int) $fila['orden_id'],
                    'producto' => (string) $fila['producto'],
                    'fecha_entrega' => $fila['fecha_entrega'],
                    'dias_retraso' => $dias,
                ];
                $impacto += $dias;
            }
        }

        usort($retrasos, fn (array $a, array $b): int => $b['dias_retraso'] <=> $a['dias_retraso']);

        $escenario = EscenarioDeSimulacion::query()->create([
            'articulo_id' => $articuloId,
            'cantidad_ficticia' => $cantidad,
            'fecha_limite' => $fechaLimite,
            'prioridad' => 1,
            'impacto_proyectado' => round($impacto, 2),
        ]);

        return [
            'escenario_id' => (int) $escenario->id,
            'viable' => $viable,
            'motivo' => $viable ? null : 'no_llega_a_la_fecha_limite',
            'pedido' => [
                'articulo_id' => $articuloId,
                'articulo' => $filaPedido['producto'] ?? null,
                'cantidad' => $cantidad,
                'fecha_limite' => $fechaLimite,
                'fecha_fin_proyectada' => $finPedido->toDateString(),
            ],
            'retrasos' => $retrasos,
            'impacto_proyectado' => round($impacto, 2),
        ];
    }

    private function ordenFicticia(int $articuloId, float $cantidad, string $fechaLimite): OrdenDeProduccion
    {
        $ficticia = new OrdenDeProduccion([
            'articulo_id' => $articuloId,
            'estado' => 'Pendiente',
            'cantidad_requerida' => $cantidad,
            'fecha_entrega' => $fechaLimite,
            'prioridad_actual' => 1,
            'cantidad_producida' => 0,
        ]);

        // Never persisted: a negative key keeps it out of the real data.
        $ficticia->id = -1;

        return $ficticia;
    }

    /**
     * @param  array{etapas: array<int, array{fin_en: string}>}  $fila
     */
    private function fechaFin(array $fila): ?CarbonImmutable
    {
        $fin = collect($fila['etapas'])->pluck('fin_en')->filter()->max();

        return $fin !== null ? CarbonImmutable::parse($fin) : null;
    }
}
