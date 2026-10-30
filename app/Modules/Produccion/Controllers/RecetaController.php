<?php

namespace App\Modules\Produccion\Controllers;

use App\Modules\Produccion\Models\ComponenteDeReceta;
use App\Modules\Produccion\Requests\DestroyComponenteDeRecetaRequest;
use App\Modules\Produccion\Requests\StoreComponenteDeRecetaRequest;
use App\Modules\Produccion\Services\GestionRecetasService;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Http\RedirectResponse;

class RecetaController
{
    public function __construct(private readonly GestionRecetasService $recetas) {}

    public function store(StoreComponenteDeRecetaRequest $request, int $articulo): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->recetas->agregarComponente(
            $actor,
            $articulo,
            (int) $request->validated('articulo_insumo_id'),
            (float) $request->validated('cantidad'),
            $request->ip(),
        );

        return redirect()
            ->route('produccion.hoja-de-ruta.show', $articulo)
            ->with('success', 'Insumo agregado a la receta.');
    }

    public function destroy(DestroyComponenteDeRecetaRequest $request, int $articulo, ComponenteDeReceta $componente): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->recetas->quitarComponente($actor, $articulo, $componente, $request->ip());

        return redirect()
            ->route('produccion.hoja-de-ruta.show', $articulo)
            ->with('success', 'Insumo quitado de la receta.');
    }
}
