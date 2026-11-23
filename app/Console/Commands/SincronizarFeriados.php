<?php

namespace App\Console\Commands;

use App\Modules\Produccion\Models\Feriado;
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Sync national holidays from the official open dataset (Datos Argentina,
 * CC BY 4.0). Runs daily so a decree published mid-year is picked up.
 *
 * Safety rule: a failed, empty or implausibly short response never deletes
 * anything. A reconciliation only happens over a healthy payload of the same
 * source, so an external glitch cannot wipe the calendar.
 */
class SincronizarFeriados extends Command
{
    private const URL = 'https://www.argentina.gob.ar/sites/default/files/holidays-%d-es.json';

    private const FUENTE = 'datos.gob.ar';

    private const MINIMO_PLAUSIBLE = 10;

    /**
     * @var string
     */
    protected $signature = 'feriados:sincronizar {anio?* : Años a sincronizar (por defecto el actual y el próximo)}';

    /**
     * @var string
     */
    protected $description = 'Sincroniza los feriados nacionales desde el dataset oficial de Datos Argentina.';

    public function handle(): int
    {
        $anios = $this->argument('anio');

        if ($anios === []) {
            $anios = [now()->year, now()->year + 1];
        }

        $fallo = false;

        foreach ($anios as $anio) {
            if (! $this->sincronizar((int) $anio)) {
                $fallo = true;
            }
        }

        return $fallo ? self::FAILURE : self::SUCCESS;
    }

    private function sincronizar(int $anio): bool
    {
        try {
            $respuesta = Http::timeout(10)
                ->retry(2, 200)
                ->get(sprintf(self::URL, $anio));
        } catch (RequestException $error) {
            $estado = $error->response?->status();

            if ($estado === 404) {
                // Future years are published progressively; a missing year is
                // not a failure.
                $this->line("Los feriados de {$anio} todavía no están publicados.");

                return true;
            }

            $this->warn("La consulta de feriados de {$anio} falló con HTTP ".($estado ?? 'desconocido').'.');

            return false;
        } catch (Throwable $error) {
            $this->warn("No se pudo consultar los feriados de {$anio}: ".class_basename($error).'.');

            return false;
        }

        if (! $respuesta->successful()) {
            $this->warn("La consulta de feriados de {$anio} respondió HTTP {$respuesta->status()}.");

            return false;
        }

        $feriados = $this->normalizar($respuesta->json('mainEntity.itemListElement'));

        if (count($feriados) < self::MINIMO_PLAUSIBLE) {
            $this->warn(
                "Respuesta sospechosa para {$anio}: ".count($feriados).' feriados. No se modifica la base.',
            );

            return false;
        }

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

        $this->info("Feriados {$anio}: ".count($feriados).' sincronizados.');

        return true;
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
