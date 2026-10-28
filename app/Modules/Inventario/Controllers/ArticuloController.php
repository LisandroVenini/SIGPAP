<?php

namespace App\Modules\Inventario\Controllers;

use App\Modules\Inventario\Enums\OrigenArticulo;
use App\Modules\Inventario\Enums\TipoArticulo;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Requests\CambiarEstadoArticuloRequest;
use App\Modules\Inventario\Requests\ListarArticulosRequest;
use App\Modules\Inventario\Requests\StoreArticuloRequest;
use App\Modules\Inventario\Requests\UpdateArticuloRequest;
use App\Modules\Inventario\Services\GestionArticulosService;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ArticuloController
{
    public function __construct(private readonly GestionArticulosService $articulos) {}

    public function index(ListarArticulosRequest $request): Response
    {
        $filtros = $request->validated();

        return Inertia::render('Inventario/Index', [
            'articulos' => $this->articulos->listar($filtros),
            'resumen' => $this->articulos->resumen(),
            'tipos' => TipoArticulo::values(),
            'origenes' => OrigenArticulo::values(),
            'unidades' => $this->articulos->unidades(),
            'filtros' => $filtros,
        ]);
    }

    public function store(StoreArticuloRequest $request): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->articulos->crear($actor, $request->validated(), $request->ip());

        return redirect()->route('inventario.articulos.index')->with('success', 'Artículo creado.');
    }

    public function update(UpdateArticuloRequest $request, Articulo $articulo): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->articulos->actualizar($actor, $articulo, $request->validated(), $request->ip());

        return redirect()->route('inventario.articulos.index')->with('success', 'Artículo actualizado.');
    }

    public function cambiarEstado(CambiarEstadoArticuloRequest $request, Articulo $articulo): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->articulos->cambiarEstado($actor, $articulo, $request->boolean('confirmar'), $request->ip());

        return redirect()->route('inventario.articulos.index')->with('success', 'Estado del artículo actualizado.');
    }
}
