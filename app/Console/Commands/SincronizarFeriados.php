<?php

namespace App\Console\Commands;

use App\Modules\Produccion\Services\SincronizarFeriadosService;
use Illuminate\Console\Command;

class SincronizarFeriados extends Command
{
    /**
     * @var string
     */
    protected $signature = 'feriados:sincronizar {anio?* : Años a sincronizar (por defecto el actual y el próximo)}';

    /**
     * @var string
     */
    protected $description = 'Sincroniza los feriados nacionales desde el dataset oficial de Datos Argentina.';

    public function handle(SincronizarFeriadosService $servicio): int
    {
        $anios = $this->argument('anio');
        $anios = $anios === [] ? [now()->year, now()->year + 1] : array_map('intval', $anios);

        $resultado = $servicio->sincronizar($anios);

        foreach ($resultado['anios'] as $anio) {
            match ($anio['estado']) {
                'sincronizado' => $this->info("Feriados {$anio['anio']}: {$anio['feriados']} sincronizados."),
                'no_publicado' => $this->line("Los feriados de {$anio['anio']} todavía no están publicados."),
                'sospechoso' => $this->warn("Respuesta sospechosa para {$anio['anio']}: {$anio['feriados']} feriados. No se modifica la base."),
                default => $this->warn("No se pudo consultar los feriados de {$anio['anio']}."),
            };
        }

        return $resultado['fallo'] ? self::FAILURE : self::SUCCESS;
    }
}
