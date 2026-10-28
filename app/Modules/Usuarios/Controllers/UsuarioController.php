<?php

namespace App\Modules\Usuarios\Controllers;

use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Requests\StoreUsuarioRequest;
use App\Modules\Usuarios\Requests\UpdateUsuarioRequest;
use App\Modules\Usuarios\Services\GestionUsuariosService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UsuarioController
{
    public function __construct(private readonly GestionUsuariosService $usuarios) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Usuario::class);

        $filtros = $request->only(['buscar', 'rol', 'estado']);

        return Inertia::render('Usuarios/Index', [
            'usuarios' => $this->usuarios->listar($filtros),
            'resumen' => $this->usuarios->resumen(),
            'roles' => Rol::values(),
            'filtros' => $filtros,
        ]);
    }

    public function store(StoreUsuarioRequest $request): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->usuarios->crear($actor, $request->validated(), $request->ip());

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado.');
    }

    public function update(UpdateUsuarioRequest $request, Usuario $usuario): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->usuarios->actualizar($actor, $usuario, $request->validated(), $request->ip());

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado.');
    }

    public function alternarEstado(Request $request, Usuario $usuario): RedirectResponse
    {
        Gate::authorize('cambiarEstado', $usuario);

        /** @var Usuario $actor */
        $actor = $request->user();

        $this->usuarios->alternarEstado($actor, $usuario, $request->ip());

        return redirect()->route('usuarios.index')->with('success', 'Estado del usuario actualizado.');
    }
}
