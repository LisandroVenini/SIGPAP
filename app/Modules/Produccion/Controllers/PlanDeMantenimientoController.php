<?php

namespace App\Modules\Produccion\Controllers;

use App\Modules\Produccion\Models\PlanDeMantenimiento;
use App\Modules\Produccion\Requests\DestroyPlanRequest;
use App\Modules\Produccion\Requests\ListarPlanesRequest;
use App\Modules\Produccion\Requests\StorePlanRequest;
use App\Modules\Produccion\Requests\UpdatePlanRequest;
use App\Modules\Produccion\Services\GestionMantenimientosService;
use App\Modules\Produccion\Services\GestionMaquinariasService;
use App\Modules\Produccion\Services\GestionPlanesDeMantenimientoService;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PlanDeMantenimientoController
{
    public function __construct(
        private readonly GestionMaquinariasService $maquinarias,
        private readonly GestionMantenimientosService $mantenimientos,
        private readonly GestionPlanesDeMantenimientoService $planes,
    ) {}

    public function index(ListarPlanesRequest $request): Response
    {
        $filtros = $request->validated();

        return Inertia::render('Produccion/Mantenimiento/Index', [
            'planes' => $this->planes->listar($filtros),
            'intervenciones' => $this->mantenimientos->intervenciones(),
            'maquinarias' => $this->maquinarias->opciones(),
            'filtros' => $filtros,
        ]);
    }

    public function store(StorePlanRequest $request): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->planes->crear($actor, $request->validated(), $request->ip());

        return redirect()->route('produccion.planes.index')->with('success', 'Plan de mantenimiento creado.');
    }

    public function update(UpdatePlanRequest $request, PlanDeMantenimiento $plan): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->planes->actualizar($actor, $plan, $request->validated(), $request->ip());

        return redirect()->route('produccion.planes.index')->with('success', 'Plan de mantenimiento actualizado.');
    }

    public function destroy(DestroyPlanRequest $request, PlanDeMantenimiento $plan): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->planes->eliminar($actor, $plan, $request->ip());

        return redirect()->route('produccion.planes.index')->with('success', 'Plan de mantenimiento eliminado.');
    }
}
