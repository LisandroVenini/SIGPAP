<?php

namespace App\Modules\Compras\Controllers;

use App\Modules\Compras\Services\GestionRankingService;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RankingController
{
    public function __construct(private readonly GestionRankingService $ranking) {}

    public function index(Request $request): Response
    {
        $actor = $request->user();

        abort_unless($actor instanceof Usuario && $this->ranking->puedeVer($actor), 403);

        $datos = $this->ranking->listar();

        return Inertia::render('Compras/Ranking/Index', [
            'parametros' => $datos['parametros'],
            'filas' => $datos['filas'],
            'sinHistorial' => $datos['sin_historial'],
        ]);
    }
}
