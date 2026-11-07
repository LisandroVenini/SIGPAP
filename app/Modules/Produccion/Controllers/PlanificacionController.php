<?php

namespace App\Modules\Produccion\Controllers;

use App\Modules\Produccion\Models\PlanificacionDeProduccion;
use App\Modules\Produccion\Requests\DestroyPlanificacionRequest;
use App\Modules\Produccion\Requests\ListarPlanificacionesRequest;
use App\Modules\Produccion\Requests\StorePlanificacionRequest;
use App\Modules\Produccion\Requests\UpdatePlanificacionRequest;
use App\Modules\Produccion\Services\GestionPlanificacionesService;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PlanificacionController
{
    public function __construct(private readonly GestionPlanificacionesService $planificaciones) {}

    public function index(ListarPlanificacionesRequest $request): Response
    {
        $filtros = $request->validated();

        return Inertia::render('Produccion/Planificaciones/Index', [
            'planificaciones' => $this->planificaciones->listar($filtros),
            'filtros' => $filtros,
        ]);
    }

    public function store(StorePlanificacionRequest $request): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->planificaciones->crear($actor, $request->validated(), $request->ip());

        return redirect()->route('produccion.planificaciones.index')->with('success', 'Planificación creada.');
    }

    public function update(UpdatePlanificacionRequest $request, PlanificacionDeProduccion $planificacion): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->planificaciones->actualizar($actor, $planificacion, $request->validated(), $request->ip());

        return redirect()->route('produccion.planificaciones.index')->with('success', 'Planificación actualizada.');
    }

    public function destroy(DestroyPlanificacionRequest $request, PlanificacionDeProduccion $planificacion): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->planificaciones->eliminar($actor, $planificacion, $request->ip());

        return redirect()->route('produccion.planificaciones.index')->with('success', 'Planificación eliminada.');
    }
}
