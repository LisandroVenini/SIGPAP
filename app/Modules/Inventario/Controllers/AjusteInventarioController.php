<?php

namespace App\Modules\Inventario\Controllers;

use App\Modules\Inventario\Enums\MotivoAjuste;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Requests\AjustarInventarioRequest;
use App\Modules\Inventario\Requests\ListarArticulosRequest;
use App\Modules\Inventario\Services\GestionAjustesService;
use App\Modules\Inventario\Services\GestionArticulosService;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AjusteInventarioController
{
    public function __construct(
        private readonly GestionAjustesService $ajustes,
        private readonly GestionArticulosService $articulos,
    ) {}

    public function index(ListarArticulosRequest $request): Response
    {
        $filtros = $request->validated();

        return Inertia::render('Inventario/Ajuste', [
            'articulos' => $this->articulos->listar($filtros),
            'motivos' => MotivoAjuste::values(),
            'filtros' => $filtros,
        ]);
    }

    public function store(AjustarInventarioRequest $request, Articulo $articulo): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->ajustes->ajustar(
            $actor,
            $articulo,
            (float) $request->validated('nuevo_stock'),
            $request->validated('motivo'),
            $request->ip(),
        );

        return redirect()->route('inventario.ajustes.index')->with('success', 'Ajuste de inventario registrado.');
    }
}
