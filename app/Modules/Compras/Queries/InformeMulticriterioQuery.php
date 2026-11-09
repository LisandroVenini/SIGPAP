<?php

namespace App\Modules\Compras\Queries;

use App\Modules\Compras\Models\Cotizacion;
use Illuminate\Support\Collection;

class InformeMulticriterioQuery
{
    /**
     * Quotes of a solicitud with their supplier, lines and the quantities requested
     * by the solicitud, eager loaded so the report never triggers N+1 queries.
     *
     * @return Collection<int, Cotizacion>
     */
    public function cotizacionesDeSolicitud(int $solicitudId): Collection
    {
        return Cotizacion::query()
            ->where('solicitud_de_cotizacion_id', $solicitudId)
            ->with([
                'proveedor:id,razon_social',
                'lineas:id,cotizacion_id,articulo_id,precio_unitario,cantidad_ofertada,fecha_prometida',
                'solicitudDeCotizacion:id',
                'solicitudDeCotizacion.lineas:id,solicitud_de_cotizacion_id,articulo_id,cantidad_solicitada',
            ])
            ->orderBy('id')
            ->get();
    }
}
