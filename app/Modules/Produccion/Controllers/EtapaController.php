<?php

namespace App\Modules\Produccion\Controllers;

use App\Modules\Produccion\Models\Etapa;
use App\Modules\Produccion\Requests\DestroyEtapaRequest;
use App\Modules\Produccion\Requests\ListarEtapasRequest;
use App\Modules\Produccion\Requests\StoreEtapaRequest;
use App\Modules\Produccion\Requests\UpdateEtapaRequest;
use App\Modules\Produccion\Services\GestionEtapasService;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EtapaController
{
    public function __construct(private readonly GestionEtapasService $etapas) {}

    public function index(ListarEtapasRequest $request): Response
    {
        $filtros = $request->validated();

        return Inertia::render('Produccion/Etapas/Index', [
            'etapas' => $this->etapas->listar($filtros),
            'filtros' => $filtros,
        ]);
    }

    public function store(StoreEtapaRequest $request): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->etapas->crear($actor, $request->validated(), $request->ip());

        return redirect()->route('produccion.etapas.index')->with('success', 'Etapa creada.');
    }

    public function update(UpdateEtapaRequest $request, Etapa $etapa): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->etapas->actualizar($actor, $etapa, $request->validated(), $request->ip());

        return redirect()->route('produccion.etapas.index')->with('success', 'Etapa actualizada.');
    }

    public function destroy(DestroyEtapaRequest $request, Etapa $etapa): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->etapas->eliminar($actor, $etapa, $request->ip());

        return redirect()->route('produccion.etapas.index')->with('success', 'Etapa eliminada.');
    }
}
