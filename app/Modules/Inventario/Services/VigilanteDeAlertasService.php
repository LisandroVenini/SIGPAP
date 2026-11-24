<?php

namespace App\Modules\Inventario\Services;

use App\Modules\Inventario\Enums\EstadoArticulo;
use App\Modules\Inventario\Models\Articulo;
use App\Shared\Alertas\Services\AlertaService;

class VigilanteDeAlertasService
{
    public function __construct(
        private readonly AlertaService $alertas,
        private readonly EvaluarStockMinimoService $stockMinimo,
    ) {}

    /**
     * Re-evaluate the inventory conditions and sync their alerts.
     */
    public function evaluar(): int
    {
        return $this->evaluarStockBajo()
            + $this->evaluarVencimientos();
    }

    private function evaluarStockBajo(): int
    {
        $bajos = 0;

        Articulo::query()
            ->where('estado', EstadoArticulo::Activo->value)
            ->whereNotNull('stock_minimo')
            ->pluck('id')
            ->each(function (int $articuloId) use (&$bajos): void {
                if ($this->stockMinimo->evaluar($articuloId)) {
                    $bajos++;
                }
            });

        return $bajos;
    }

    private function evaluarVencimientos(): int
    {
        $porVencer = [];
        $vencidos = [];

        Articulo::query()
            ->whereNotNull('fecha_vencimiento')
            ->where('stock_fisico', '>', 0)
            ->get()
            ->each(function (Articulo $articulo) use (&$porVencer, &$vencidos): void {
                $dias = (int) round(today()->diffInDays($articulo->fecha_vencimiento, false));
                $referencia = 'articulo:'.$articulo->id;
                $contexto = [
                    'articulo' => $articulo->descripcion,
                    'fecha_vencimiento' => $articulo->fecha_vencimiento->toDateString(),
                    'stock' => (float) $articulo->stock_fisico,
                    'articulo_id' => $articulo->id,
                ];

                if ($dias < 0) {
                    $vencidos[$referencia] = $contexto;

                    return;
                }

                $porVencer[$referencia] = [...$contexto, 'dias' => $dias];
            });

        return $this->alertas->sincronizarPorTipo('articulo_por_vencer', $porVencer)
            + $this->alertas->sincronizarPorTipo('articulo_vencido', $vencidos);
    }
}
