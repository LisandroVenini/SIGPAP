<?php

namespace App\Modules\Alertas\Controllers;

use App\Modules\Alertas\Requests\FiltrosDeAlertaRequest;
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

    public function index(FiltrosDeAlertaRequest $request): Response
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $filtros = $request->validated();

        return Inertia::render('Alertas/Index', [
            'alertas' => $this->query->listar($actor, $filtros),
            'filtros' => $filtros,
            'noLeidas' => $this->query->noLeidas($actor),
            'tipos' => CatalogoDeAlertas::opciones(),
            'severidades' => SeveridadAlerta::opciones(),
        ]);
    }

    public function marcarLeida(Request $request, Alerta $alerta): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        abort_unless($this->query->esVisiblePara($actor, $alerta), 403);

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
}
