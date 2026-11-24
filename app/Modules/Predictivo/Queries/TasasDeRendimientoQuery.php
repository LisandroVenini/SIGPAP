<?php

namespace App\Modules\Predictivo\Queries;

use App\Modules\Predictivo\Models\TasaDeRendimiento;

class TasasDeRendimientoQuery
{
    /**
     * Latest calculated rate per stage.
     *
     * The schema associates the rate with the stage only, so several orders of
     * the same stage interleave rows; the tablero shows the most recent one.
     *
     * @return array<int, array{valor: float, desviacion: float, fecha_calculo: string|null}>
     */
    public function ultimasPorEtapa(): array
    {
        $ultimas = TasaDeRendimiento::query()
            ->selectRaw('max(id) as id')
            ->groupBy('etapa_id');

        return TasaDeRendimiento::query()
            ->whereIn('id', $ultimas)
            ->get()
            ->mapWithKeys(fn (TasaDeRendimiento $tasa): array => [
                (int) $tasa->etapa_id => [
                    'valor' => (float) $tasa->valor,
                    'desviacion' => (float) $tasa->desviacion,
                    'fecha_calculo' => $tasa->fecha_calculo?->toDateString(),
                ],
            ])
            ->all();
    }
}
