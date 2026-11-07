<?php

namespace App\Modules\Compras\Controllers;

use App\Modules\Compras\Models\OrdenDeCompra;
use App\Modules\Compras\Requests\AnularOrdenRequest;
use App\Modules\Compras\Requests\EmitirOrdenRequest;
use App\Modules\Compras\Requests\ListarOrdenesRequest;
use App\Modules\Compras\Requests\UpdateOrdenRequest;
use App\Modules\Compras\Services\GestionOrdenesDeCompraService;
use App\Modules\Inventario\Services\GestionArticulosService;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OrdenDeCompraController
{
    public function __construct(
        private readonly GestionOrdenesDeCompraService $ordenes,
        private readonly GestionArticulosService $articulos,
    ) {}

    public function index(ListarOrdenesRequest $request): Response
    {
        return Inertia::render('Compras/OrdenesDeCompra/Index', [
            'ordenes' => $this->ordenes->listar($request->validated()),
            'solicitudesConCotizaciones' => $this->ordenes->solicitudesConCotizaciones(),
            'articulos' => $this->articulos->insumosDisponibles(),
        ]);
    }

    public function store(EmitirOrdenRequest $request): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->ordenes->emitir(
            $actor,
            (int) $request->validated('cotizacion_id'),
            $request->ip(),
        );

        return redirect()
            ->route('compras.ordenes-de-compra.index')
            ->with('success', 'Orden de compra emitida.');
    }

    public function update(UpdateOrdenRequest $request, OrdenDeCompra $orden): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->ordenes->actualizar(
            $actor,
            $orden,
            $request->validated('lineas'),
            $request->ip(),
        );

        return redirect()
            ->route('compras.ordenes-de-compra.index')
            ->with('success', 'Orden de compra actualizada.');
    }

    public function anular(AnularOrdenRequest $request, OrdenDeCompra $orden): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->ordenes->anular(
            $actor,
            $orden,
            $request->validated('motivo'),
            $request->ip(),
        );

        return redirect()
            ->route('compras.ordenes-de-compra.index')
            ->with('success', 'Orden de compra anulada.');
    }
}
