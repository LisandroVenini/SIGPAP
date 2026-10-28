<?php

namespace App\Modules\Usuarios\Controllers;

use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Requests\ActualizarConfiguracionRequest;
use App\Modules\Usuarios\Services\ParametroGlobalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConfiguracionController
{
    public function __construct(private readonly ParametroGlobalService $parametros) {}

    public function index(Request $request): Response
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        abort_unless($this->parametros->puedeVer($actor), 403);

        return Inertia::render('Usuarios/Configuracion', [
            'parametros' => $this->parametros->listar(),
            'puedeEditar' => $this->parametros->puedeEditar($actor),
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
