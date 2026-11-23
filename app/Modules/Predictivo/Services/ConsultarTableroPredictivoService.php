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
