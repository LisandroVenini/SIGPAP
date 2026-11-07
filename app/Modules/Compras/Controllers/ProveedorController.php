<?php

namespace App\Modules\Compras\Controllers;

use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Requests\DestroyProveedorRequest;
use App\Modules\Compras\Requests\ListarProveedoresRequest;
use App\Modules\Compras\Requests\StoreProveedorRequest;
use App\Modules\Compras\Requests\UpdateProveedorRequest;
use App\Modules\Compras\Services\GestionProveedoresService;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProveedorController
{
    public function __construct(private readonly GestionProveedoresService $proveedores) {}

    public function index(ListarProveedoresRequest $request): Response
    {
        $filtros = $request->validated();

        return Inertia::render('Compras/Proveedores/Index', [
            'proveedores' => $this->proveedores->listar($filtros),
            'filtros' => $filtros,
        ]);
    }

    public function store(StoreProveedorRequest $request): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->proveedores->crear($actor, $request->validated(), $request->ip());

        return redirect()->route('compras.proveedores.index')->with('success', 'Proveedor creado.');
    }

    public function update(UpdateProveedorRequest $request, Proveedor $proveedor): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->proveedores->actualizar($actor, $proveedor, $request->validated(), $request->ip());

        return redirect()->route('compras.proveedores.index')->with('success', 'Proveedor actualizado.');
    }

    public function destroy(DestroyProveedorRequest $request, Proveedor $proveedor): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->proveedores->darDeBaja($actor, $proveedor, $request->ip());

        return redirect()->route('compras.proveedores.index')->with('success', 'Proveedor dado de baja.');
    }
}
