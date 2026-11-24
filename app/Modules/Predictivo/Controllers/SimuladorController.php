<?php

namespace App\Modules\Predictivo\Controllers;

use App\Modules\Inventario\Services\GestionArticulosService;
use App\Modules\Predictivo\Requests\SimularEscenarioRequest;
use App\Modules\Predictivo\Services\SimularEscenarioService;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SimuladorController
{
    public function __construct(
        private readonly SimularEscenarioService $simulador,
        private readonly GestionArticulosService $articulos,
    ) {}

    public function index(Request $request): Response
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        abort_unless($actor->can('predictivo.simular'), 403);

        return Inertia::render('Predictivo/Simulador/Index', $this->payload(null));
    }

    public function store(SimularEscenarioRequest $request): Response
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        abort_unless($actor->can('predictivo.simular'), 403);

        $datos = $request->validated();

        $resultado = $this->simulador->simular(
            (int) $datos['articulo_id'],
            (float) $datos['cantidad'],
            (string) $datos['fecha_limite'],
        );

        return Inertia::render('Predictivo/Simulador/Index', $this->payload($resultado, $datos));
    }

    /**
     * @param  array<string, mixed>|null  $resultado
     * @param  array<string, mixed>  $filtros
     * @return array<string, mixed>
     */
    private function payload(?array $resultado, array $filtros = []): array
    {
        return [
            'productos' => $this->articulos->productosTerminadosFabricados()->values()->all(),
            'resultado' => $resultado,
            'filtros' => [
                'articulo_id' => $filtros['articulo_id'] ?? null,
                'cantidad' => $filtros['cantidad'] ?? null,
                'fecha_limite' => $filtros['fecha_limite'] ?? null,
            ],
        ];
    }
}
