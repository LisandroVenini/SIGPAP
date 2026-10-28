<?php

namespace App\Modules\Produccion\Controllers;

use App\Modules\Produccion\Models\Operario;
use App\Modules\Produccion\Requests\DestroyOperarioRequest;
use App\Modules\Produccion\Requests\ListarOperariosRequest;
use App\Modules\Produccion\Requests\StoreOperarioRequest;
use App\Modules\Produccion\Requests\UpdateOperarioRequest;
use App\Modules\Produccion\Services\GestionOperariosService;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OperarioController
{
    public function __construct(private readonly GestionOperariosService $operarios) {}

    public function index(ListarOperariosRequest $request): Response
    {
        $filtros = $request->validated();

        return Inertia::render('Produccion/Operarios/Index', [
            'operarios' => $this->operarios->listar($filtros),
            'filtros' => $filtros,
        ]);
    }

    public function store(StoreOperarioRequest $request): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->operarios->crear($actor, $request->validated(), $request->ip());

        return redirect()->route('produccion.operarios.index')->with('success', 'Operario registrado.');
    }

    public function update(UpdateOperarioRequest $request, Operario $operario): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->operarios->actualizar($actor, $operario, $request->validated(), $request->ip());

        return redirect()->route('produccion.operarios.index')->with('success', 'Operario actualizado.');
    }

    public function destroy(DestroyOperarioRequest $request, Operario $operario): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->operarios->eliminar($actor, $operario, $request->ip());

        return redirect()->route('produccion.operarios.index')->with('success', 'Operario dado de baja.');
    }
}
