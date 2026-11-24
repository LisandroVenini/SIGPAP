<?php

namespace App\Modules\Inventario\Services;

use App\Modules\Compras\Services\GestionSolicitudesDeCotizacionService;
use App\Modules\Inventario\Enums\EstadoArticulo;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Queries\ReservasQuery;
use App\Shared\Alertas\Services\AlertaService;

/**
 * Keeps the minimum-stock condition in sync as soon as the stock changes.
 *
 * Below the minimum (available stock, reservations included) an article gets
 * the alert and a draft request for quotation with the best suppliers, ready
 * for the buyer to review, adjust and send.
 */
class EvaluarStockMinimoService
{
    public function __construct(
        private readonly ReservasQuery $reservas,
        private readonly AlertaService $alertas,
        private readonly GestionSolicitudesDeCotizacionService $compras,
    ) {}

    /**
     * @return bool whether the article is below its minimum after the change
     */
    public function evaluar(int $articuloId): bool
    {
        $articulo = Articulo::query()->find($articuloId);

        if ($articulo === null
            || $articulo->estado !== EstadoArticulo::Activo->value
            || $articulo->stock_minimo === null) {
            return false;
        }

        $minimo = (float) $articulo->stock_minimo;

        if ($minimo <= 0) {
            return false;
        }

        $comprometido = $this->reservas->comprometidoPorArticulo([$articuloId])[$articuloId] ?? 0.0;
        $disponible = round((float) $articulo->stock_fisico - $comprometido, 2);

        if ($disponible > $minimo) {
            $this->alertas->resolverPorTipo('stock_bajo_minimo', 'articulo:'.$articuloId);

            return false;
        }

        $this->alertas->emitirPorTipo('stock_bajo_minimo', [
            'articulo' => $articulo->descripcion,
            'disponible' => $disponible,
            'minimo' => $minimo,
            'articulo_id' => $articuloId,
        ], 'articulo:'.$articuloId);

        $maximo = (float) ($articulo->stock_maximo ?? 0);
        $cantidad = max(0.0, round(($maximo > 0 ? $maximo : $minimo) - $disponible, 2));

        if ($cantidad > 0) {
            $this->compras->crearBorradorPorRiesgo($articuloId, $cantidad);
        }

        return true;
    }
}
