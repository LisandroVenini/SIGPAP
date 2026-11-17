<?php

namespace App\Modules\Produccion\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Produccion\Models\PlanificacionDeProduccion;
use App\Modules\Produccion\Queries\PlanificacionesQuery;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\UsuariosService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionPlanificacionesService
{
    private const DIAS_MAXIMOS = 90;

    public function __construct(
        private readonly AuditoriaService $auditoria,
        private readonly PlanificacionesQuery $query,
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
        return $this->query->listar($filtros);
    }

    /**
     * @return array<int, array{id: int, nombre: string}>
     */
    public function opciones(): array
    {
        return PlanificacionDeProduccion::query()
            ->orderByDesc('fecha_inicio')
            ->orderBy('nombre')
            ->limit(200)
            ->get(['id', 'nombre'])
            ->map(fn (PlanificacionDeProduccion $planificacion): array => [
                'id' => $planificacion->id,
                'nombre' => $planificacion->nombre,
            ])
            ->all();
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
        $this->asegurarPeriodoValido($datos, $planificacion);

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
    private function asegurarPeriodoValido(array $datos, ?PlanificacionDeProduccion $actual = null): void
    {
        $inicio = Carbon::parse($datos['fecha_inicio']);
        $fin = Carbon::parse($datos['fecha_fin']);

        if ($fin->lt($inicio)) {
            throw ValidationException::withMessages([
                'fecha_fin' => 'La fecha de fin no puede ser anterior a la fecha de inicio.',
            ]);
        }

        if ($inicio->diffInDays($fin) > self::DIAS_MAXIMOS) {
            throw ValidationException::withMessages([
                'fecha_fin' => 'La planificación no puede durar más de '.self::DIAS_MAXIMOS.' días.',
            ]);
        }

        $solapada = PlanificacionDeProduccion::query()
            ->when($actual !== null, fn ($query) => $query->whereKeyNot($actual->getKey()))
            ->where('fecha_inicio', '<=', $fin->toDateString())
            ->where('fecha_fin', '>=', $inicio->toDateString())
            ->first();

        if ($solapada !== null) {
            throw ValidationException::withMessages([
                'fecha_inicio' => "El período se superpone con la planificación \"{$solapada->nombre}\".",
            ]);
        }
    }
}
