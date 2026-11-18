<?php

namespace App\Modules\Alertas\Controllers;

use App\Modules\Usuarios\Models\Usuario;
use App\Shared\Alertas\Catalogo\CatalogoDeAlertas;
use App\Shared\Alertas\Enums\SeveridadAlerta;
use App\Shared\Alertas\Models\Alerta;
use App\Shared\Alertas\Queries\AlertaQuery;
use App\Shared\Alertas\Services\AlertaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlertaController
{
    public function __construct(
        private readonly AlertaQuery $query,
        private readonly AlertaService $alertas,
    ) {}

    public function index(Request $request): Response
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $filtros = $request->validate([
            'estado' => ['nullable', 'in:abiertas,todas'],
            'severidad' => ['nullable', 'in:'.implode(',', SeveridadAlerta::values())],
            'tipo' => ['nullable', 'string'],
            'no_leidas' => ['nullable', 'boolean'],
        ]);

        return Inertia::render('Alertas/Index', [
            'alertas' => $this->query->listar($actor, $filtros),
            'filtros' => $filtros,
            'noLeidas' => $this->query->noLeidas($actor),
            'tipos' => collect(CatalogoDeAlertas::todos())
                ->map(fn ($tipo): array => ['clave' => $tipo->clave, 'etiqueta' => $tipo->etiqueta])
                ->values()
                ->all(),
            'severidades' => array_map(
                fn (SeveridadAlerta $severidad): array => ['valor' => $severidad->value, 'etiqueta' => $severidad->etiqueta()],
                SeveridadAlerta::cases(),
            ),
        ]);
    }

    public function marcarLeida(Request $request, Alerta $alerta): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        abort_unless($this->puedeVer($actor, $alerta), 403);

        $this->alertas->marcarLeida($actor, $alerta);

        return back()->with('success', 'Alerta marcada como leída.');
    }

    public function marcarTodas(Request $request): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->alertas->marcarTodasLeidas($actor);

        return back()->with('success', 'Todas las alertas se marcaron como leídas.');
    }

    private function puedeVer(Usuario $actor, Alerta $alerta): bool
    {
        return $this->query->visiblesPara($actor)->whereKey($alerta->id)->exists();
    }
}
