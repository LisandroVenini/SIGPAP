<?php

namespace App\Modules\Produccion\Controllers;

use App\Modules\Produccion\Enums\MotivoCambioPrioridad;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Requests\ReordenarOrdenRequest;
use App\Modules\Produccion\Requests\VerCronogramaRequest;
use App\Modules\Produccion\Services\GestionCronogramaService;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CronogramaController
{
    public function __construct(private readonly GestionCronogramaService $cronograma) {}

    public function index(VerCronogramaRequest $request): Response
    {
        return Inertia::render('Produccion/Cronograma/Index', [
            'cronograma' => $this->cronograma->proyectar(),
            'motivos' => MotivoCambioPrioridad::values(),
        ]);
    }

    public function reordenar(ReordenarOrdenRequest $request, OrdenDeProduccion $orden): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->cronograma->reordenar(
            $actor,
            $orden,
            (int) $request->validated('prioridad'),
            (string) $request->validated('motivo'),
            $request->validated('detalle'),
            (bool) ($request->validated('confirmar_conflicto') ?? false),
            $request->ip(),
        );

        return redirect()
            ->route('produccion.cronograma.index')
            ->with('success', 'Cronograma actualizado.');
    }
}
