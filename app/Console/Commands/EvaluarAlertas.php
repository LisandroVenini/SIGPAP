<?php

namespace App\Console\Commands;

use App\Modules\Compras\Services\VigilanteDeAlertasService as VigilanteCompras;
use App\Modules\Inventario\Services\VigilanteDeAlertasService as VigilanteInventario;
use App\Modules\Produccion\Services\VigilanteDeAlertasService as VigilanteProduccion;
use App\Shared\Alertas\Services\AlertaService;
use Illuminate\Console\Command;

class EvaluarAlertas extends Command
{
    /**
     * @var string
     */
    protected $signature = 'alertas:evaluar';

    /**
     * @var string
     */
    protected $description = 'Evalúa las condiciones de alerta y las sincroniza con sus reglas.';

    public function handle(
        VigilanteInventario $inventario,
        VigilanteCompras $compras,
        VigilanteProduccion $produccion,
        AlertaService $alertas,
    ): int {
        $emitidas = $inventario->evaluar() + $compras->evaluar() + $produccion->evaluar();
        $archivadas = $alertas->archivarAntiguas(90);

        $this->info("Alertas evaluadas: {$emitidas} emitidas, {$archivadas} archivadas.");

        return self::SUCCESS;
    }
}
