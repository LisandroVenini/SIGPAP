<?php

namespace App\Modules\Compras\Queries;

use App\Modules\Compras\Models\DesempenoProveedor;
use Illuminate\Support\Collection;

class RankingProveedoresQuery
{
    /**
     * Deliveries registered inside the window, with the incidents of their order.
     *
     * @return Collection<int, DesempenoProveedor>
     */
    public function entregasEnVentana(int $meses): Collection
    {
        $desde = now()->subMonthsNoOverflow($meses)->startOfDay();

        return DesempenoProveedor::query()
            ->with(['ordenDeCompra.incidencias'])
            ->whereDate('fecha_registro', '>=', $desde->toDateString())
            ->orderBy('fecha_registro')
            ->orderBy('id')
            ->get([
                'id',
                'proveedor_id',
                'orden_de_compra_id',
                'tiempo_entrega_real',
                'fecha_registro',
            ]);
    }
}
