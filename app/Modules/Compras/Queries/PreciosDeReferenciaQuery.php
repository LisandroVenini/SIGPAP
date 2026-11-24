<?php

namespace App\Modules\Compras\Queries;

use Illuminate\Support\Facades\DB;

class PreciosDeReferenciaQuery
{
    /**
     * Last unit price quoted by the supplier for the article.
     */
    public function ultimoPrecio(int $articuloId, int $proveedorId, ?int $excluirCotizacionId = null): ?float
    {
        $precio = DB::table('lineas_de_cotizacion')
            ->join('cotizaciones', 'cotizaciones.id', '=', 'lineas_de_cotizacion.cotizacion_id')
            ->where('lineas_de_cotizacion.articulo_id', $articuloId)
            ->where('cotizaciones.proveedor_id', $proveedorId)
            ->when(
                $excluirCotizacionId !== null,
                fn ($query) => $query->where('cotizaciones.id', '<>', $excluirCotizacionId),
            )
            ->orderByDesc('cotizaciones.fecha')
            ->orderByDesc('cotizaciones.id')
            ->value('lineas_de_cotizacion.precio_unitario');

        return $precio !== null ? (float) $precio : null;
    }

    /**
     * Last unit price quoted for the article by any supplier.
     */
    public function ultimoPrecioDeCualquierProveedor(int $articuloId): ?float
    {
        $precio = DB::table('lineas_de_cotizacion')
            ->join('cotizaciones', 'cotizaciones.id', '=', 'lineas_de_cotizacion.cotizacion_id')
            ->where('lineas_de_cotizacion.articulo_id', $articuloId)
            ->orderByDesc('cotizaciones.fecha')
            ->orderByDesc('cotizaciones.id')
            ->value('lineas_de_cotizacion.precio_unitario');

        return $precio !== null ? (float) $precio : null;
    }
}
