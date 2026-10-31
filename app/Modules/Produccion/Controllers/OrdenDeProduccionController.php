<?php

namespace App\Modules\Produccion\Controllers;

use App\Modules\Inventario\Services\GestionArticulosService;
use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use App\Modules\Produccion\Enums\ModoDeAltaOrden;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Requests\AnularOrdenDeProduccionRequest;
use App\Modules\Produccion\Requests\AsignarRecursosRequest;
use App\Modules\Produccion\Requests\CerrarOrdenRequest;
use App\Modules\Produccion\Requests\IniciarOrdenRequest;
use App\Modules\Produccion\Requests\ListarOrdenesDeProduccionRequest;
use App\Modules\Produccion\Requests\PosponerOrdenRequest;
use App\Modules\Produccion\Requests\ReanudarOrdenRequest;
use App\Modules\Produccion\Requests\RegistrarCalidadRequest;
use App\Modules\Produccion\Requests\StoreOrdenDeProduccionRequest;
use App\Modules\Produccion\Requests\UpdateOrdenDeProduccionRequest;
use App\Modules\Produccion\Services\GestionCalidadService;
use App\Modules\Produccion\Services\GestionCierreService;
use App\Modules\Produccion\Services\GestionOrdenesDeProduccionService;
use App\Modules\Produccion\Services\GestionPlanificacionesService;
use App\Modules\Produccion\Services\GestionRecursosDeOrdenService;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OrdenDeProduccionController
{
    public function __construct(
        private readonly GestionOrdenesDeProduccionService $ordenes,
        private readonly GestionPlanificacionesService $planificaciones,
        private readonly GestionArticulosService $articulos,
        private readonly GestionRecursosDeOrdenService $recursos,
        private readonly GestionCalidadService $calidad,
        private readonly GestionCierreService $cierre,
    ) {}

    public function index(ListarOrdenesDeProduccionRequest $request): Response
    {
        $filtros = $request->validated();
        $recursosDisponibles = $this->recursos->recursosDisponibles();

        return Inertia::render('Produccion/OrdenesDeProduccion/Index', [
            'ordenes' => $this->ordenes->listar($filtros),
            'planificaciones' => $this->planificaciones->opciones(),
            'productos' => $this->articulos->productosTerminadosFabricados()->values()->all(),
            'estados' => EstadoOrdenProduccion::values(),
            'filtros' => $filtros,
            'operariosDisponibles' => $recursosDisponibles['operarios'],
            'maquinariasDisponibles' => $recursosDisponibles['maquinarias'],
        ]);
    }

    public function store(StoreOrdenDeProduccionRequest $request): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $datos = $request->validated();
        $resultado = $this->ordenes->crear(
            $actor,
            (int) $datos['planificacion_de_produccion_id'],
            $datos['fecha_entrega'],
            $datos['lineas'],
            ModoDeAltaOrden::from($datos['modo']),
            $request->ip(),
        );

        $mensaje = sprintf(
            'Creadas: %d, pendientes por insumos: %d, omitidas: %d.',
            $resultado['creadas'],
            $resultado['pendientes'],
            $resultado['omitidas'],
        );

        return redirect()->route('produccion.ordenes-de-produccion.index')->with('success', $mensaje);
    }

    public function update(UpdateOrdenDeProduccionRequest $request, OrdenDeProduccion $orden): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $datos = $request->validated();
        $this->ordenes->modificar(
            $actor,
            $orden,
            (float) $datos['cantidad'],
            $datos['fecha_entrega'],
            $request->ip(),
        );

        return redirect()->route('produccion.ordenes-de-produccion.index')->with('success', 'Orden modificada.');
    }

    public function anular(AnularOrdenDeProduccionRequest $request, OrdenDeProduccion $orden): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->ordenes->anular(
            $actor,
            $orden,
            $request->validated('motivo'),
            $request->ip(),
        );

        return redirect()->route('produccion.ordenes-de-produccion.index')->with('success', 'Orden anulada.');
    }

    public function asignar(AsignarRecursosRequest $request, OrdenDeProduccion $orden): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $datos = $request->validated();

        $this->recursos->asignar(
            $actor,
            $orden,
            $datos['operarios'] ?? [],
            $datos['maquinarias'] ?? [],
            $request->ip(),
        );

        return redirect()->route('produccion.ordenes-de-produccion.index')->with('success', 'Recursos asignados.');
    }

    public function iniciar(IniciarOrdenRequest $request, OrdenDeProduccion $orden): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->recursos->iniciar($actor, $orden, $request->ip());

        return redirect()->route('produccion.ordenes-de-produccion.index')->with('success', 'Orden iniciada.');
    }

    public function registrarCalidad(RegistrarCalidadRequest $request, OrdenDeProduccion $orden): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $datos = $request->validated();

        $this->calidad->registrar(
            $actor,
            $orden,
            $datos['parte_diario_id'] ?? null,
            (float) $datos['cantidad_rechazada'],
            $datos['motivo'] ?? null,
            $request->ip(),
        );

        return redirect()->route('produccion.ordenes-de-produccion.index')->with('success', 'Control de calidad registrado.');
    }

    public function cerrar(CerrarOrdenRequest $request, OrdenDeProduccion $orden): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $this->cierre->cerrar(
            $actor,
            $orden,
            (float) $request->validated('unidades_finales'),
            $request->ip(),
        );

        return redirect()->route('produccion.ordenes-de-produccion.index')->with('success', 'Orden cerrada.');
    }

    public function posponer(PosponerOrdenRequest $request, OrdenDeProduccion $orden): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $datos = $request->validated();

        $this->cierre->posponer(
            $actor,
            $orden,
            $datos['fecha_prevista_inicio'] ?? null,
            (bool) $datos['liberar_reservas'],
            $datos['motivo'] ?? null,
            $request->ip(),
        );

        return redirect()->route('produccion.ordenes-de-produccion.index')->with('success', 'Orden pospuesta.');
    }

    public function reanudar(ReanudarOrdenRequest $request, OrdenDeProduccion $orden): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $resultado = $this->cierre->reanudar($actor, $orden, $request->ip());

        if ($resultado['estado'] === EstadoOrdenProduccion::PendientePorInsumos->value) {
            $mensaje = 'No hay stock suficiente para reanudar. Faltantes: '.collect($resultado['faltantes'])
                ->map(fn (float $cantidad, int $articuloId): string => "#{$articuloId}: {$cantidad}")
                ->implode(', ');

            return redirect()->route('produccion.ordenes-de-produccion.index')
                ->withErrors(['insumos' => $mensaje]);
        }

        return redirect()->route('produccion.ordenes-de-produccion.index')->with('success', 'Orden reanudada.');
    }
}
