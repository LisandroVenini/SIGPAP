<?php

namespace App\Modules\Predictivo\Controllers;

use App\Modules\Predictivo\Requests\FiltrosTableroRequest;
use App\Modules\Predictivo\Services\ConsultarTableroPredictivoService;
use App\Modules\Usuarios\Models\Usuario;
use Inertia\Inertia;
use Inertia\Response;

class TableroPredictivoController
{
    public function __construct(private readonly ConsultarTableroPredictivoService $tablero) {}

    public function index(FiltrosTableroRequest $request): Response
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        abort_unless($this->tablero->puedeConsultar($actor), 403);

        return Inertia::render('Predictivo/Tablero/Index', $this->tablero->para($actor, $request->validated()));
    }
}
