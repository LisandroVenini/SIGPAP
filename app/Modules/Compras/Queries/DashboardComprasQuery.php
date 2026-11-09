<?php

namespace App\Modules\Compras\Queries;

use App\Modules\Compras\Enums\EstadoSolicitudDeCotizacion;
use App\Modules\Compras\Models\OrdenDeCompra;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Models\SolicitudDeCotizacion;

class DashboardComprasQuery
{
    /**
     * @return array{pendientes_recepcion: int, solicitudes_abiertas: int, proveedores_activos: int}
     */
    public function resumen(): array
    {
        return [
            'pendientes_recepcion' => OrdenDeCompra::query()->recepcionables()->count(),
            'solicitudes_abiertas' => SolicitudDeCotizacion::query()
                ->where('estado', EstadoSolicitudDeCotizacion::Enviada->value)
                ->count(),
            'proveedores_activos' => Proveedor::query()->activos()->count(),
        ];
    }
}
