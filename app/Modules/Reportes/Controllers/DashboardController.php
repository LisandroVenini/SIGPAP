<?php

namespace App\Modules\Reportes\Controllers;

use App\Modules\Reportes\Services\DashboardService;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function __invoke(Request $request): Response
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        return Inertia::render('Dashboard', [
            'resumen' => $this->dashboard->resumen($actor),
        ]);
    }
}
