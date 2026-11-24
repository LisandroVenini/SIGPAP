<?php

namespace App\Modules\Compras\Controllers;

use App\Modules\Compras\Requests\StoreSolicitudDisponibilidadRequest;
use App\Modules\Compras\Services\GestionDisponibilidadService;
use App\Modules\Inventario\Services\ConsultaInventarioService;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DisponibilidadController
{
    public function __construct(
        private readonly GestionDisponibilidadService $disponibilidad,
        private readonly ConsultaInventarioService $inventario,
    ) {}

    public function index(Request $request): Response
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        abort_unless($this->disponibilidad->puedeGestionar($actor), 403);

        return Inertia::render('Compras/Disponibilidad/Index', [
            'bajos' => $this->bajosDeStock(),
            'solicitudes' => $this->disponibilidad->listarConRecomendacion(),
            'proveedores' => $this->disponibilidad->proveedoresConEmail(),
            'proveedorSugerido' => $this->disponibilidad->proveedorSugerido(),
        ]);
    }

    public function store(StoreSolicitudDisponibilidadRequest $request): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        abort_unless($this->disponibilidad->puedeGestionar($actor), 403);

        $datos = $request->validated();

        $this->disponibilidad->crear(
            $actor,
            (int) $datos['articulo_id'],
            (float) $datos['cantidad'],
            (int) $datos['proveedor_id'],
            $datos['fecha_requerida'] ?? null,
            $request->ip(),
        );

        return back()->with('success', 'Consulta de disponibilidad enviada.');
    }

    /**
     * @return array<int, array{articulo_id:int, articulo:string, disponible:float, minimo:float}>
     */
    private function bajosDeStock(): array
    {
        return collect($this->inventario->datosDeInventario()['articulos'])
            ->filter(fn (array $articulo): bool => $articulo['stock_minimo'] > 0
                && $articulo['disponible'] <= $articulo['stock_minimo'])
            ->map(fn (array $articulo): array => [
                'articulo_id' => (int) $articulo['id'],
                'articulo' => (string) $articulo['descripcion'],
                'disponible' => (float) $articulo['disponible'],
                'minimo' => (float) $articulo['stock_minimo'],
            ])
            ->values()
            ->all();
    }
}
