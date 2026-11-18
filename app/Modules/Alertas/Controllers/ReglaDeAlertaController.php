<?php

namespace App\Modules\Alertas\Controllers;

use App\Modules\Alertas\Requests\DestroyReglaRequest;
use App\Modules\Alertas\Requests\ListarReglasRequest;
use App\Modules\Alertas\Requests\StoreReglaRequest;
use App\Modules\Alertas\Requests\ToggleReglaRequest;
use App\Modules\Alertas\Requests\UpdateReglaRequest;
use App\Modules\Alertas\Services\GestionReglasDeAlertaService;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use App\Shared\Alertas\Catalogo\CatalogoDeAlertas;
use App\Shared\Alertas\Catalogo\TipoDeAlerta;
use App\Shared\Alertas\Enums\ModuloDeAlerta;
use App\Shared\Alertas\Enums\SeveridadAlerta;
use App\Shared\Alertas\Models\ReglaDeAlerta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class ReglaDeAlertaController
{
    public function __construct(private readonly GestionReglasDeAlertaService $reglas) {}

    public function index(ListarReglasRequest $request): Response
    {
        Gate::authorize('viewAny', ReglaDeAlerta::class);

        /** @var Usuario $actor */
        $actor = $request->user();

        return Inertia::render('Alertas/Reglas/Index', [
            'reglas' => $this->reglas->listar($request->validated()),
            'filtros' => $request->validated(),
            'tipos' => $this->catalogo(),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            'modulos' => ModuloDeAlerta::values(),
            'severidades' => array_map(
                fn (SeveridadAlerta $severidad): array => ['valor' => $severidad->value, 'etiqueta' => $severidad->etiqueta()],
                SeveridadAlerta::cases(),
            ),
            'puedeGestionar' => $actor->hasAnyRole([Rol::Administrador->value, Rol::Gerente->value])
                || $actor->can('compras.gestionar')
                || $actor->can('inventario.gestionar')
                || $actor->can('produccion.gestionar'),
        ]);
    }

    public function store(StoreReglaRequest $request): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->reglas->crear($actor, $request->validated(), $request->ip());

        return back()->with('success', 'Regla de alerta creada.');
    }

    public function update(UpdateReglaRequest $request, ReglaDeAlerta $regla): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->reglas->actualizar($actor, $regla, $request->validated(), $request->ip());

        return back()->with('success', 'Regla de alerta actualizada.');
    }

    public function toggle(ToggleReglaRequest $request, ReglaDeAlerta $regla): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->reglas->alternarActiva($actor, $regla, $request->ip());

        return back()->with('success', 'Estado de la regla actualizado.');
    }

    public function destroy(DestroyReglaRequest $request, ReglaDeAlerta $regla): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->reglas->eliminar($actor, $regla, $request->ip());

        return back()->with('success', 'Regla de alerta eliminada.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function catalogo(): array
    {
        return collect(CatalogoDeAlertas::todos())
            ->map(fn (TipoDeAlerta $tipo): array => [
                'clave' => $tipo->clave,
                'etiqueta' => $tipo->etiqueta,
                'descripcion' => $tipo->descripcion,
                'modulo' => $tipo->modulo->value,
                'origen' => $tipo->origen->value,
                'severidad_por_defecto' => $tipo->severidadPorDefecto->value,
                'roles_por_defecto' => array_map(fn (Rol $rol): string => $rol->value, $tipo->rolesPorDefecto),
                'condiciones' => $tipo->condiciones,
                'variables' => $tipo->variables,
            ])
            ->values()
            ->all();
    }
}
