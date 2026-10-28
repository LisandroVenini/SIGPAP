<?php

namespace App\Modules\Usuarios\Controllers;

use App\Modules\Usuarios\Models\ParametroGlobal;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Requests\ActualizarConfiguracionRequest;
use App\Modules\Usuarios\Services\ParametroGlobalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ConfiguracionController
{
    public function __construct(private readonly ParametroGlobalService $parametros) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', ParametroGlobal::class);

        /** @var Usuario $actor */
        $actor = $request->user();

        return Inertia::render('Usuarios/Configuracion', [
            'parametros' => $this->parametros->listar(),
            'puedeEditar' => $actor->can('update', ParametroGlobal::class),
        ]);
    }

    public function update(ActualizarConfiguracionRequest $request): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        /** @var array<string, string> $parametros */
        $parametros = $request->validated('parametros');

        $this->parametros->actualizar($actor, $parametros, $request->ip());

        return back()->with('success', 'Parámetros actualizados.');
    }
}
