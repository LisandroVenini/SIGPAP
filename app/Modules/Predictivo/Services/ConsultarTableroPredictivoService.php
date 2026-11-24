<?php

namespace App\Modules\Predictivo\Services;

use App\Modules\Predictivo\Queries\TasasDeRendimientoQuery;
use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use App\Modules\Produccion\Services\CalendarioLaboral;
use App\Modules\Produccion\Services\GestionCronogramaService;
use App\Modules\Usuarios\Models\Usuario;
use App\Shared\Alertas\Models\Alerta;
use App\Shared\Alertas\Queries\AlertaQuery;
use Illuminate\Support\Collection;

/**
 * CU-PRED-05: read-only manager board.
 *
 * Layer 1 (plan and reality) comes from Producción's public projection; layer 2
 * (delay risk) comes from the persisted rates and the open bottleneck alerts.
 */
class ConsultarTableroPredictivoService
{
    public function __construct(
        private readonly GestionCronogramaService $cronograma,
        private readonly TasasDeRendimientoQuery $tasas,
        private readonly AlertaQuery $alertas,
        private readonly CalendarioLaboral $calendario,
    ) {}

    public function puedeConsultar(Usuario $actor): bool
    {
        return $actor->can('predictivo.consultar');
    }

    /**
     * @param  array{estado?: string|null, solo_riesgo?: bool|null}  $filtros
     * @return array<string, mixed>
     */
    public function para(Usuario $actor, array $filtros = []): array
    {
        $cronograma = $this->cronograma->proyectar();
        $filas = collect($cronograma['filas']);

        $visibles = $filas
            ->when(
                ($filtros['estado'] ?? null) !== null && $filtros['estado'] !== '',
                fn (Collection $coleccion): Collection => $coleccion->where('estado', $filtros['estado']),
            )
            ->when(
                (bool) ($filtros['solo_riesgo'] ?? false),
                fn (Collection $coleccion): Collection => $coleccion->where('conflicto_entrega', true),
            )
            ->values();

        $ultimasTasas = $this->tasas->ultimasPorEtapa();
        $valores = array_column($ultimasTasas, 'valor');

        return [
            'cronograma' => [
                'inicio' => $cronograma['inicio'],
                'hoy' => $cronograma['hoy'],
                'dias' => $cronograma['dias'],
                'filas' => $visibles->all(),
            ],
            'kpis' => [
                'ordenes_activas' => $visibles->count(),
                'en_riesgo' => $visibles->where('conflicto_entrega', true)->count(),
                'avance_promedio' => $visibles->isEmpty() ? 0.0 : round((float) $visibles->avg('avance'), 2),
                'tasa_promedio' => $valores === [] ? null : round(array_sum($valores) / count($valores), 4),
            ],
            'alertas' => $this->alertasVigentes($actor),
            'abastecimiento' => $this->abastecimientoVigente($actor),
            'tasas' => $ultimasTasas,
            'estados' => EstadoOrdenProduccion::values(),
            'filtros' => [
                'estado' => $filtros['estado'] ?? null,
                'solo_riesgo' => (bool) ($filtros['solo_riesgo'] ?? false),
            ],
            'sinDatos' => $cronograma['filas'] === [],
            'calendarioConfigurado' => $this->calendario->configurado(),
        ];
    }

    /**
     * Supply layer: the open stock-break alerts carry the dates the manager
     * needs, so the board shows the last evaluated state without recalculating.
     *
     * @return array<int, array{articulo_id: int|null, articulo: string, disponible: float, minimo: float, dias_hasta_quiebre: mixed, fecha_limite_pedido: mixed, fecha_quiebre: mixed, cantidad_sugerida: float, riesgo: string}>
     */
    private function abastecimientoVigente(Usuario $actor): array
    {
        return $this->alertas->visiblesPara($actor)
            ->where('tipo', 'quiebre_de_stock')
            ->whereNull('resuelta_en')
            ->orderBy('fecha')
            ->get()
            ->map(function (Alerta $alerta): array {
                $contexto = $alerta->contexto ?? [];

                return [
                    'articulo_id' => $alerta->articulo_id !== null ? (int) $alerta->articulo_id : null,
                    'articulo' => (string) ($contexto['articulo'] ?? 'Artículo '.$alerta->articulo_id),
                    'disponible' => (float) ($contexto['disponible'] ?? 0),
                    'minimo' => (float) ($contexto['minimo'] ?? 0),
                    'dias_hasta_quiebre' => $contexto['dias_hasta_quiebre'] ?? null,
                    'fecha_limite_pedido' => $contexto['fecha_limite_pedido'] ?? null,
                    'fecha_quiebre' => $contexto['fecha_quiebre'] ?? null,
                    'cantidad_sugerida' => (float) ($contexto['cantidad_sugerida'] ?? 0),
                    'riesgo' => (string) ($contexto['riesgo'] ?? 'alto'),
                ];
            })
            ->sortBy(fn (array $fila): string => (string) ($fila['fecha_limite_pedido'] ?? '9999-12-31'))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: int, descripcion: string, severidad: string, fecha: string|null, orden_de_produccion_id: int|null}>
     */
    private function alertasVigentes(Usuario $actor): array
    {
        return $this->alertas->visiblesPara($actor)
            ->where('tipo', 'cuello_de_botella_predictivo')
            ->whereNull('resuelta_en')
            ->orderByDesc('fecha')
            ->limit(20)
            ->get()
            ->map(fn (Alerta $alerta): array => [
                'id' => $alerta->id,
                'descripcion' => $alerta->descripcion,
                'severidad' => (string) $alerta->severidad,
                'fecha' => $alerta->fecha?->toDateTimeString(),
                'orden_de_produccion_id' => $alerta->orden_de_produccion_id !== null
                    ? (int) $alerta->orden_de_produccion_id
                    : null,
            ])
            ->values()
            ->all();
    }
}
