<?php

namespace App\Modules\Compras\Controllers;

use App\Modules\Compras\Models\SolicitudDeCotizacion;
use App\Modules\Compras\Requests\AnularSolicitudRequest;
use App\Modules\Compras\Requests\ListarSolicitudesRequest;
use App\Modules\Compras\Requests\StoreSolicitudRequest;
use App\Modules\Compras\Requests\UpdateSolicitudRequest;
use App\Modules\Compras\Services\GestionSolicitudesDeCotizacionService;
use App\Modules\Inventario\Services\GestionArticulosService;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SolicitudDeCotizacionController
{
    public function __construct(
        private readonly GestionSolicitudesDeCotizacionService $solicitudes,
        private readonly GestionArticulosService $articulos,
    ) {}

    public function index(ListarSolicitudesRequest $request): Response
    {
        return Inertia::render('Compras/SolicitudesDeCotizacion/Index', [
            'solicitudes' => $this->solicitudes->listar($request->validated()),
            'articulos' => $this->articulos->insumosDisponibles(),
            'proveedores' => $this->solicitudes->proveedoresActivos(),
        ]);
    }

    public function store(StoreSolicitudRequest $request): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->solicitudes->crear($actor, $request->validated(), $request->ip());

        return redirect()
            ->route('compras.solicitudes-de-cotizacion.index')
            ->with('success', 'Solicitud de cotización creada.');
    }

    public function update(UpdateSolicitudRequest $request, SolicitudDeCotizacion $solicitud): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->solicitudes->actualizar($actor, $solicitud, $request->validated(), $request->ip());

        return redirect()
            ->route('compras.solicitudes-de-cotizacion.index')
            ->with('success', 'Solicitud de cotización actualizada.');
    }

    public function anular(AnularSolicitudRequest $request, SolicitudDeCotizacion $solicitud): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->solicitudes->anular($actor, $solicitud, $request->validated('motivo'), $request->ip());

        return redirect()
            ->route('compras.solicitudes-de-cotizacion.index')
            ->with('success', 'Solicitud de cotización anulada.');
    }
}
