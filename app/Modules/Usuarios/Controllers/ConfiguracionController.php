<?php

namespace App\Modules\Usuarios\Controllers;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\ParametroGlobal;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\ParametroGlobalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ConfiguracionController
{
    public function __construct(
        private readonly ParametroGlobalService $parametros,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function index(Request $request): Response
    {
        $actor = $this->autorizarAcceso($request);

        return Inertia::render('Usuarios/Configuracion', [
            'parametros' => $this->parametros->listar(),
            'puedeEditar' => $actor->hasRole(Rol::Administrador->value),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $actor = $this->autorizarAcceso($request);

        $validated = $request->validate([
            'parametros' => ['required', 'array'],
            'parametros.*' => ['required', 'string'],
        ]);

        DB::transaction(function () use ($validated, $actor, $request): void {
            foreach ($validated['parametros'] as $nombre => $valor) {
                $parametro = $this->parametros->buscar($nombre);
                abort_if($parametro === null, 404);

                $valorAnterior = $parametro->valor;

                $this->parametros->definirParametro(
                    $actor,
                    $nombre,
                    $valor,
                    $parametro->categoria,
                );

                $this->auditoria->registrarEvento(
                    $actor->id,
                    'actualizar_parametro',
                    ['valor' => $valorAnterior],
                    ['valor' => $valor],
                    $request->ip(),
                    ParametroGlobal::class,
                    $parametro->id,
                );
            }
        });

        return back()->with('success', 'Parámetros actualizados.');
    }

    /**
     * The configuration screen is visible to Administrador and Gerente;
     * writing remains restricted to Administrador by the service.
     */
    private function autorizarAcceso(Request $request): Usuario
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        if (! $actor->hasAnyRole([Rol::Administrador->value, Rol::Gerente->value])) {
            abort(403);
        }

        return $actor;
    }
}
