<?php

namespace App\Modules\Compras\Services;

use App\Modules\Compras\Enums\EstadoOrdenDeCompra;
use App\Modules\Compras\Enums\EstadoSolicitudDeCotizacion;
use App\Modules\Compras\Models\OrdenDeCompra;
use App\Modules\Compras\Models\SolicitudDeCotizacion;
use App\Shared\Alertas\Services\AlertaService;

class VigilanteDeAlertasService
{
    public function __construct(private readonly AlertaService $alertas) {}

    public function evaluar(): int
    {
        return $this->evaluarSolicitudes() + $this->evaluarOrdenesSinConfirmar();
    }

    private function evaluarSolicitudes(): int
    {
        $porVencer = [];
        $insuficientes = [];

        SolicitudDeCotizacion::query()
            ->where('estado', EstadoSolicitudDeCotizacion::Enviada->value)
            ->withCount(['cotizaciones', 'proveedores'])
            ->get()
            ->each(function (SolicitudDeCotizacion $solicitud) use (&$porVencer, &$insuficientes): void {
                $dias = (int) round(today()->diffInDays($solicitud->fecha_vencimiento, false));
                $referencia = 'solicitud:'.$solicitud->id;
                $contexto = [
                    'numero' => '#'.$solicitud->id,
                    'fecha_vencimiento' => $solicitud->fecha_vencimiento->toDateString(),
                    'dias' => $dias,
                    'proveedores' => (int) $solicitud->proveedores_count,
                    'solicitud_de_cotizacion_id' => $solicitud->id,
                ];

                $porVencer[$referencia] = $contexto;
                $insuficientes[$referencia] = [
                    ...$contexto,
                    'recibidas' => (int) $solicitud->cotizaciones_count,
                ];
            });

        return $this->alertas->sincronizarPorTipo('solicitud_por_vencer', $porVencer)
            + $this->alertas->sincronizarPorTipo('cotizaciones_insuficientes', $insuficientes);
    }

    private function evaluarOrdenesSinConfirmar(): int
    {
        $contextos = [];

        OrdenDeCompra::query()
            ->where('estado', EstadoOrdenDeCompra::Emitida->value)
            ->with('proveedor')
            ->get()
            ->each(function (OrdenDeCompra $orden) use (&$contextos): void {
                $dias = (int) round(today()->diffInDays($orden->fecha_emision, false));

                $contextos['oc:'.$orden->id] = [
                    'numero_oc' => $orden->numero_oc,
                    'proveedor' => $orden->proveedor?->razon_social ?? '—',
                    'dias' => $dias,
                    'orden_de_compra_id' => $orden->id,
                ];
            });

        return $this->alertas->sincronizarPorTipo('oc_sin_confirmar', $contextos);
    }
}
