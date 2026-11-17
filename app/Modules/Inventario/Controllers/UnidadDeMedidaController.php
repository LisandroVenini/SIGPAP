<?php

namespace App\Modules\Inventario\Controllers;

use App\Modules\Inventario\Models\UnidadDeMedida;
use App\Modules\Inventario\Requests\DestroyUnidadDeMedidaRequest;
use App\Modules\Inventario\Requests\ListarUnidadesDeMedidaRequest;
use App\Modules\Inventario\Requests\StoreUnidadDeMedidaRequest;
use App\Modules\Inventario\Requests\UpdateUnidadDeMedidaRequest;
use App\Modules\Inventario\Services\GestionUnidadesDeMedidaService;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UnidadDeMedidaController
{
    public function __construct(private readonly GestionUnidadesDeMedidaService $unidades) {}

    public function index(ListarUnidadesDeMedidaRequest $request): Response
    {
        return Inertia::render('Inventario/UnidadesDeMedida/Index', [
            'unidades' => $this->unidades->listar(),
        ]);
    }

    public function store(StoreUnidadDeMedidaRequest $request): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->unidades->crear(
            $actor,
            $request->validated('nombre'),
            $request->ip(),
        );

        return redirect()->route('inventario.unidades-de-medida.index')->with('success', 'Unidad de medida creada.');
    }

    public function update(UpdateUnidadDeMedidaRequest $request, UnidadDeMedida $unidad): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->unidades->actualizar(
            $actor,
            $unidad,
            $request->validated('nombre'),
            $request->ip(),
        );

        return redirect()->route('inventario.unidades-de-medida.index')->with('success', 'Unidad de medida actualizada.');
    }

    public function destroy(DestroyUnidadDeMedidaRequest $request, UnidadDeMedida $unidad): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->unidades->eliminar($actor, $unidad, $request->ip());

        return redirect()->route('inventario.unidades-de-medida.index')->with('success', 'Unidad de medida eliminada.');
    }
}
