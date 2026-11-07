<?php

namespace App\Modules\Inventario\Controllers;

use App\Modules\Compras\Services\GestionRecepcionesService;
use App\Modules\Inventario\Requests\RegistrarIngresoRequest;
use App\Modules\Inventario\Services\GestionIngresosService;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IngresoMercanciaController
{
    public function __construct(
        private readonly GestionIngresosService $ingresos,
        private readonly GestionRecepcionesService $recepciones,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('inventario.gestionar'), 403);

        return Inertia::render('Inventario/Ingresos/Index', [
            'ordenesRecepcionables' => $this->recepciones->ordenesRecepcionables(),
            'filtros' => [],
        ]);
    }

    public function store(RegistrarIngresoRequest $request): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->ingresos->registrar(
            $actor,
            $request->validated('numero_oc'),
            $request->validated('numero_remito'),
            $request->validated('lineas'),
            (bool) $request->validated('entrega_parcial', false),
            (bool) $request->validated('confirmar_excedente', false),
            $request->ip(),
        );

        return redirect()
            ->route('inventario.ingresos.index')
            ->with('success', 'Ingreso registrado.');
    }
}
