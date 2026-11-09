<?php

namespace App\Modules\Reportes\Controllers;

use App\Modules\Reportes\Requests\ExportarReporteRequest;
use App\Modules\Reportes\Requests\GenerarReporteRequest;
use App\Modules\Reportes\Services\ExportadorReportesService;
use App\Modules\Reportes\Services\GestionReportesService;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReporteController
{
    public function __construct(
        private readonly GestionReportesService $reportes,
        private readonly ExportadorReportesService $exportador,
    ) {}

    public function index(GenerarReporteRequest $request): InertiaResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();
        $filtros = $request->validated();

        return Inertia::render('Reportes/Index', [
            'tipos' => $this->reportes->tiposDisponibles($actor),
            'filtros' => $filtros,
            'preview' => isset($filtros['tipo'])
                ? $this->reportes->generar($actor, $filtros['tipo'], $filtros)
                : null,
        ]);
    }

    public function exportar(ExportarReporteRequest $request): Response|BinaryFileResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();
        $datos = $request->validated();
        $reporte = $this->reportes->generar($actor, $datos['tipo'], $datos);

        return $this->exportador->exportar($reporte, $datos['formato']);
    }
}
