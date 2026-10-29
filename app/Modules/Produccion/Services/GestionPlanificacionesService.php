<?php

namespace App\Modules\Produccion\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Produccion\Models\PlanificacionDeProduccion;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\UsuariosService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionPlanificacionesService
{
    public function __construct(
        private readonly AuditoriaService $auditoria,
        private readonly UsuariosService $usuarios,
    ) {}

    public function puedeGestionar(Usuario $actor): bool
    {
        return $this->usuarios->autorizar($actor, 'produccion.gestionar');
    }

    /**
     * @param  array{buscar?: string|null}  $filtros
     */
    public function listar(array $filtros = []): LengthAwarePaginator
    {
        return PlanificacionDeProduccion::query()
            ->withCount('ordenesDeProduccion')
            ->when($filtros['buscar'] ?? null, fn ($query, string $buscar) => $query->buscar($buscar))
            ->orderByDesc('fecha_inicio')
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (PlanificacionDeProduccion $planificacion) => [
                'id' => $planificacion->id,
                'nombre' => $planificacion->nombre,
                'fecha_inicio' => $planificacion->fecha_inicio?->toDateString(),
                'fecha_fin' => $planificacion->fecha_fin?->toDateString(),
                'observaciones' => $planificacion->observaciones,
                'ordenes' => $planificacion->ordenes_de_produccion_count,
            ]);
    }

    /**
     * @param  array{nombre: string, fecha_inicio: string, fecha_fin: string, observaciones?: string|null}  $datos
     */
    public function crear(Usuario $actor, array $datos, ?string $direccionIp = null): PlanificacionDeProduccion
    {
        $this->asegurarPeriodoValido($datos);

        return DB::transaction(function () use ($actor, $datos, $direccionIp): PlanificacionDeProduccion {
            $planificacion = PlanificacionDeProduccion::create($datos);

            $this->auditoria->registrarEvento(
                $actor->id,
                'crear_planificacion',
                null,
                [
                    'nombre' => $planificacion->nombre,
                    'fecha_inicio' => $planificacion->fecha_inicio?->toDateString(),
                    'fecha_fin' => $planificacion->fecha_fin?->toDateString(),
                ],
                $direccionIp,
                PlanificacionDeProduccion::class,
                $planificacion->id,
            );

            return $planificacion;
        });
    }

    /**
     * @param  array{nombre: string, fecha_inicio: string, fecha_fin: string, observaciones?: string|null}  $datos
     */
    public function actualizar(
        Usuario $actor,
        PlanificacionDeProduccion $planificacion,
        array $datos,
        ?string $direccionIp = null,
    ): PlanificacionDeProduccion {
        $this->asegurarPeriodoValido($datos);

        return DB::transaction(function () use ($actor, $planificacion, $datos, $direccionIp): PlanificacionDeProduccion {
            $anterior = [
                'nombre' => $planificacion->nombre,
                'fecha_inicio' => $planificacion->fecha_inicio?->toDateString(),
                'fecha_fin' => $planificacion->fecha_fin?->toDateString(),
                'observaciones' => $planificacion->observaciones,
            ];

            $nuevo = [
                'nombre' => $datos['nombre'],
                'fecha_inicio' => Carbon::parse($datos['fecha_inicio'])->toDateString(),
                'fecha_fin' => Carbon::parse($datos['fecha_fin'])->toDateString(),
                'observaciones' => $datos['observaciones'] ?? null,
            ];

            $planificacion->update($nuevo);

            $this->auditoria->registrarEvento(
                $actor->id,
                'actualizar_planificacion',
                $anterior,
                $nuevo,
                $direccionIp,
                PlanificacionDeProduccion::class,
                $planificacion->id,
            );

            return $planificacion;
        });
    }

    public function eliminar(
        Usuario $actor,
        PlanificacionDeProduccion $planificacion,
        ?string $direccionIp = null,
    ): void {
        DB::transaction(function () use ($actor, $planificacion, $direccionIp): void {
            if ($planificacion->ordenesDeProduccion()->exists()) {
                throw ValidationException::withMessages([
                    'planificacion' => 'La planificación tiene órdenes de producción asociadas. Eliminá esas órdenes antes de borrarla.',
                ]);
            }

            $this->auditoria->registrarEvento(
                $actor->id,
                'eliminar_planificacion',
                [
                    'nombre' => $planificacion->nombre,
                    'fecha_inicio' => $planificacion->fecha_inicio?->toDateString(),
                    'fecha_fin' => $planificacion->fecha_fin?->toDateString(),
                ],
                null,
                $direccionIp,
                PlanificacionDeProduccion::class,
                $planificacion->id,
            );

            $planificacion->delete();
        });
    }

    /**
     * @param  array{fecha_inicio: string, fecha_fin: string}  $datos
     */
    private function asegurarPeriodoValido(array $datos): void
    {
        if (Carbon::parse($datos['fecha_fin'])->lt(Carbon::parse($datos['fecha_inicio']))) {
            throw ValidationException::withMessages([
                'fecha_fin' => 'La fecha de fin no puede ser anterior a la fecha de inicio.',
            ]);
        }
    }
}
