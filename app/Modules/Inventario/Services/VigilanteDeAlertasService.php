<?php

namespace App\Modules\Inventario\Services;

use App\Modules\Inventario\Models\Articulo;
use App\Shared\Alertas\Services\AlertaService;

class VigilanteDeAlertasService
{
    public function __construct(private readonly AlertaService $alertas) {}

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
        $contextos = [];

        Articulo::query()
            ->where('estado', 'Activo')
            ->whereNotNull('stock_minimo')
            ->whereColumn('stock_fisico', '<', 'stock_minimo')
            ->get()
            ->each(function (Articulo $articulo) use (&$contextos): void {
                $contextos['articulo:'.$articulo->id] = [
                    'articulo' => $articulo->descripcion,
                    'disponible' => (float) $articulo->stock_fisico,
                    'minimo' => (float) $articulo->stock_minimo,
                    'articulo_id' => $articulo->id,
                ];
            });

        return $this->alertas->sincronizarPorTipo('stock_bajo_minimo', $contextos);
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
