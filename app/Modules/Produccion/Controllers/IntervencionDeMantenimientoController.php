<?php

namespace App\Modules\Produccion\Controllers;

use App\Modules\Produccion\Models\Maquinaria;
use App\Modules\Produccion\Requests\StoreIntervencionRequest;
use App\Modules\Produccion\Services\GestionMantenimientosService;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Http\RedirectResponse;

class IntervencionDeMantenimientoController
{
    public function __construct(private readonly GestionMantenimientosService $mantenimientos) {}

    public function store(StoreIntervencionRequest $request, Maquinaria $maquinaria): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();
        $datos = $request->validated();

        $this->mantenimientos->registrar(
            $actor,
            $maquinaria,
            $datos['fecha'],
            $datos['tipo'],
            $datos['responsable'],
            $datos['observaciones'] ?? null,
            $request->ip(),
        );

        return redirect()->route('produccion.planes.index')->with('success', 'Intervención registrada.');
    }
}
