<?php

namespace App\Modules\Produccion\Controllers;

use App\Modules\Produccion\Requests\AsignarEtapaRequest;
use App\Modules\Produccion\Requests\DesasignarEtapaRequest;
use App\Modules\Produccion\Requests\GestionarHojaDeRutaRequest;
use App\Modules\Produccion\Services\GestionHojaDeRutaService;
use App\Modules\Produccion\Services\GestionRecetasService;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class HojaDeRutaController
{
    public function __construct(
        private readonly GestionHojaDeRutaService $hoja,
        private readonly GestionRecetasService $recetas,
    ) {}

    public function index(GestionarHojaDeRutaRequest $request): Response
    {
        $filtros = $request->validated();

        return Inertia::render('Produccion/HojaDeRuta/Index', [
            'productos' => $this->hoja->productos($filtros),
            'filtros' => $filtros,
        ]);
    }

    /**
     * The request is only the authorization boundary; its payload is not consumed here.
     */
    public function show(GestionarHojaDeRutaRequest $request, int $articulo): Response
    {
        $detalle = $this->hoja->detalle($articulo);

        if ($detalle === null) {
            abort(404);
        }

        return Inertia::render('Produccion/HojaDeRuta/Detalle', [
            ...$detalle,
            'receta' => $this->recetas->detalle($articulo),
        ]);
    }

    public function store(AsignarEtapaRequest $request, int $articulo): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->hoja->asignar(
            $actor,
            $articulo,
            (int) $request->validated('etapa_id'),
            (float) $request->validated('tiempo_por_unidad'),
            $request->ip(),
        );

        return redirect()
            ->route('produccion.hoja-de-ruta.show', $articulo)
            ->with('success', 'Etapa asignada a la hoja de ruta.');
    }

    public function destroy(DesasignarEtapaRequest $request, int $articulo, int $etapa): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->hoja->desasignar($actor, $articulo, $etapa, $request->ip());

        return redirect()
            ->route('produccion.hoja-de-ruta.show', $articulo)
            ->with('success', 'Etapa desasignada.');
    }
}
