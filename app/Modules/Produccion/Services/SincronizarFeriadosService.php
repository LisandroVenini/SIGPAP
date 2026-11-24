<?php

namespace App\Modules\Produccion\Services;

use App\Modules\Produccion\Models\Feriado;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Sync national holidays from the official open dataset (Datos Argentina,
 * CC BY 4.0). The model and the rule live in Producción, so the console
 * command only delegates here.
 *
 * Safety rule: a failed, empty or implausibly short response never deletes
 * anything. A reconciliation only happens over a healthy payload of the same
 * source, so an external glitch cannot wipe the calendar.
 */
class SincronizarFeriadosService
{
    private const URL = 'https://www.argentina.gob.ar/sites/default/files/holidays-%d-es.json';

    private const FUENTE = 'datos.gob.ar';

    private const MINIMO_PLAUSIBLE = 10;

    private const INTENTOS = 2;

    /**
     * @param  array<int, int>  $anios
     * @return array{fallo: bool, anios: array<int, array{anio:int, estado:string, feriados:int}>}
     */
    public function sincronizar(array $anios): array
    {
        $resultados = [];
        $fallo = false;

        foreach ($anios as $anio) {
            $resultado = $this->sincronizarAnio((int) $anio);
            $resultados[] = $resultado;

            if (in_array($resultado['estado'], ['fallo', 'sospechoso'], true)) {
                $fallo = true;
            }
        }

        return ['fallo' => $fallo, 'anios' => $resultados];
    }

    /**
     * @return array{anio:int, estado:string, feriados:int}
     */
    private function sincronizarAnio(int $anio): array
    {
        $respuesta = $this->obtener($anio);

        if ($respuesta === null) {
            return ['anio' => $anio, 'estado' => 'fallo', 'feriados' => 0];
        }

        if ($respuesta->status() === 404) {
            return ['anio' => $anio, 'estado' => 'no_publicado', 'feriados' => 0];
        }

        if (! $respuesta->successful()) {
            return ['anio' => $anio, 'estado' => 'fallo', 'feriados' => 0];
        }

        $feriados = $this->normalizar($respuesta->json('mainEntity.itemListElement'));

        if (count($feriados) < self::MINIMO_PLAUSIBLE) {
            return ['anio' => $anio, 'estado' => 'sospechoso', 'feriados' => count($feriados)];
        }

        $this->persistir($anio, $feriados);

        return ['anio' => $anio, 'estado' => 'sincronizado', 'feriados' => count($feriados)];
    }

    private function obtener(int $anio): ?Response
    {
        for ($intento = 1; $intento <= self::INTENTOS; $intento++) {
            try {
                return Http::timeout(10)->get(sprintf(self::URL, $anio));
            } catch (Throwable) {
                // Connection failure: retry once, then give up.
            }
        }

        return null;
    }

    /**
     * @param  array<string, array{nombre: string, tipo: string}>  $feriados
     */
    private function persistir(int $anio, array $feriados): void
    {
        $ahora = now();

        DB::transaction(function () use ($feriados, $anio, $ahora): void {
            foreach ($feriados as $fecha => $datos) {
                Feriado::query()->updateOrCreate(
                    ['fecha' => $fecha],
                    [
                        'nombre' => $datos['nombre'],
                        'tipo' => $datos['tipo'],
                        'fuente' => self::FUENTE,
                        'sincronizado_en' => $ahora,
                    ],
                );
            }

            // Reconcile only our own source and only the requested year: a
            // holiday removed upstream disappears, manual entries survive.
            Feriado::query()
                ->whereYear('fecha', $anio)
                ->where('fuente', self::FUENTE)
                ->whereNotIn('fecha', array_keys($feriados))
                ->delete();
        });
    }

    /**
     * @return array<string, array{nombre: string, tipo: string}>
     */
    private function normalizar(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $normalizados = [];

        foreach ($items as $item) {
            $nodo = is_array($item) ? ($item['item'] ?? null) : null;

            if (! is_array($nodo)) {
                continue;
            }

            $fecha = $nodo['startDate'] ?? null;

            if (! is_string($fecha) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) !== 1) {
                continue;
            }

            $tipo = $nodo['additionalProperty']['value'] ?? 'desconocido';

            $normalizados[$fecha] = [
                'nombre' => (string) ($nodo['name'] ?? 'Feriado'),
                'tipo' => is_string($tipo) ? $tipo : 'desconocido',
            ];
        }

        return $normalizados;
    }
}
