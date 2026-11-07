<?php

namespace App\Modules\Compras\Queries;

use App\Modules\Compras\Models\SolicitudDeCotizacion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SolicitudesDeCotizacionQuery
{
    /**
     * @param  array{estado?: string|null, proveedor?: string|null, fecha_desde?: string|null, fecha_hasta?: string|null}  $filtros
     */
    public function listar(array $filtros = []): LengthAwarePaginator
    {
        return SolicitudDeCotizacion::query()
            ->withCount(['lineas', 'proveedores', 'cotizaciones'])
            ->when($filtros['estado'] ?? null, fn (Builder $query, string $estado) => $query->where('estado', $estado))
            ->when(
                $filtros['proveedor'] ?? null,
                fn (Builder $query, string $proveedor) => $query->whereHas(
                    'proveedores',
                    fn (Builder $query) => $query->where('razon_social', 'ilike', '%'.addcslashes($proveedor, '%_\\').'%'),
                ),
            )
            ->when(
                $filtros['fecha_desde'] ?? null,
                fn (Builder $query, string $fecha) => $query->whereDate('fecha', '>=', $fecha),
            )
            ->when(
                $filtros['fecha_hasta'] ?? null,
                fn (Builder $query, string $fecha) => $query->whereDate('fecha', '<=', $fecha),
            )
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (SolicitudDeCotizacion $solicitud): array => [
                'id' => $solicitud->id,
                'fecha' => $solicitud->fecha->format('Y-m-d'),
                'fecha_vencimiento' => $solicitud->fecha_vencimiento->format('Y-m-d'),
                'estado' => $solicitud->estado,
                'lineas' => $solicitud->lineas_count,
                'proveedores' => $solicitud->proveedores_count,
                'cotizaciones' => $solicitud->cotizaciones_count,
            ]);
    }

    /**
     * Sent solicitudes that already received quotes, with the data needed to compare them.
     *
     * @return Collection<int, SolicitudDeCotizacion>
     */
    public function conCotizaciones(): Collection
    {
        return SolicitudDeCotizacion::query()
            ->where('estado', 'Enviada')
            ->whereHas('cotizaciones')
            ->with(['cotizaciones.proveedor', 'cotizaciones.lineas'])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();
    }
}
