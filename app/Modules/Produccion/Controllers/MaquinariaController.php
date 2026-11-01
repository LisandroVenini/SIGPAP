<?php

namespace App\Modules\Produccion\Controllers;

use App\Modules\Produccion\Models\Maquinaria;
use App\Modules\Produccion\Requests\DestroyMaquinariaRequest;
use App\Modules\Produccion\Requests\ListarMaquinariasRequest;
use App\Modules\Produccion\Requests\StoreMaquinariaRequest;
use App\Modules\Produccion\Requests\UpdateMaquinariaRequest;
use App\Modules\Produccion\Services\GestionMaquinariasService;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MaquinariaController
{
    public function __construct(private readonly GestionMaquinariasService $maquinarias) {}

    public function index(ListarMaquinariasRequest $request): Response
    {
        $filtros = $request->validated();

        return Inertia::render('Produccion/Maquinarias/Index', [
            'maquinarias' => $this->maquinarias->listar($filtros),
            'filtros' => $filtros,
        ]);
    }

    public function store(StoreMaquinariaRequest $request): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->maquinarias->crear($actor, $request->validated(), $request->ip());

        return redirect()->route('produccion.maquinarias.index')->with('success', 'Maquinaria registrada.');
    }

    public function update(UpdateMaquinariaRequest $request, Maquinaria $maquinaria): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->maquinarias->actualizar($actor, $maquinaria, $request->validated(), $request->ip());

        return redirect()->route('produccion.maquinarias.index')->with('success', 'Maquinaria actualizada.');
    }

    public function destroy(DestroyMaquinariaRequest $request, Maquinaria $maquinaria): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->maquinarias->eliminar($actor, $maquinaria, $request->ip());

        return redirect()->route('produccion.maquinarias.index')->with('success', 'Maquinaria eliminada.');
    }
}
