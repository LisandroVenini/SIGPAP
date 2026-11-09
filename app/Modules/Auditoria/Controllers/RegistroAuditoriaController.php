<?php

namespace App\Modules\Auditoria\Controllers;

use App\Modules\Auditoria\Requests\ListarEventosRequest;
use App\Modules\Auditoria\Services\ConsultaAuditoriaService;
use Inertia\Inertia;
use Inertia\Response;

class RegistroAuditoriaController
{
    public function __construct(private readonly ConsultaAuditoriaService $consulta) {}

    public function index(ListarEventosRequest $request): Response
    {
        $resultado = $this->consulta->listar($request->validated());

        return Inertia::render('Auditoria/Index', [
            'eventos' => $resultado['eventos'],
            'usuarios' => $resultado['usuarios'],
            'acciones' => $resultado['acciones'],
            'filtros' => $request->validated(),
        ]);
    }
}
