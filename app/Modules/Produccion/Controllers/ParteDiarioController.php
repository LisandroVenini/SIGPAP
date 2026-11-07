<?php

namespace App\Modules\Produccion\Controllers;

use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Requests\StoreParteDiarioRequest;
use App\Modules\Produccion\Services\GestionPartesDiariosService;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Http\RedirectResponse;

class ParteDiarioController
{
    public function __construct(private readonly GestionPartesDiariosService $partesDiarios) {}

    public function store(StoreParteDiarioRequest $request, OrdenDeProduccion $orden): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $datos = $request->validated();

        $this->partesDiarios->registrar(
            $actor,
            $orden,
            (int) $datos['etapa_id'],
            $datos['maquinaria_id'] !== null ? (int) $datos['maquinaria_id'] : null,
            $datos['fecha'],
            (float) $datos['cantidad'],
            $datos['observaciones'] ?? null,
            $request->ip(),
        );

        return redirect()
            ->route('produccion.ordenes-de-produccion.index')
            ->with('success', 'Parte diario registrado.');
    }
}
