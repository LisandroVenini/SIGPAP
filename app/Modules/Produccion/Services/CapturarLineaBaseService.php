<?php

namespace App\Modules\Produccion\Services;

use App\Modules\Produccion\Models\LineaBaseDeCronograma;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use Illuminate\Support\Facades\DB;

/**
 * Captures the committed schedule of an order once, when it is assigned.
 *
 * The baseline never changes afterwards: it is the plan the factory promised,
 * the fixed reference the projection is compared against.
 */
class CapturarLineaBaseService
{
    public function __construct(private readonly GestionCronogramaService $cronograma) {}

    public function capturar(int $ordenDeProduccionId): int
    {
        if (LineaBaseDeCronograma::query()
            ->where('orden_de_produccion_id', $ordenDeProduccionId)
            ->exists()) {
            return 0;
        }

        $fila = collect($this->cronograma->proyectar()['filas'])
            ->firstWhere('orden_id', $ordenDeProduccionId);

        $orden = OrdenDeProduccion::query()->find($ordenDeProduccionId);

        if ($fila === null || $orden === null || $fila['etapas'] === []) {
            return 0;
        }

        $filas = array_map(fn (array $etapa): array => [
            'orden_de_produccion_id' => $ordenDeProduccionId,
            'etapa_id' => $etapa['etapa_id'],
            'inicio_plan' => $etapa['inicio_en'],
            'fin_plan' => $etapa['fin_en'],
            'created_at' => now(),
            'updated_at' => now(),
        ], $fila['etapas']);

        DB::transaction(fn () => LineaBaseDeCronograma::query()->insertOrIgnore($filas));

        return count($filas);
    }
}
