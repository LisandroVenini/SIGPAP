<?php

namespace App\Modules\Produccion\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Produccion\Enums\EstadoMaquinaria;
use App\Modules\Produccion\Models\PlanDeMantenimiento;
use App\Modules\Produccion\Queries\PlanesDeMantenimientoQuery;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\UsuariosService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionPlanesDeMantenimientoService
{
    public function __construct(
        private readonly AuditoriaService $auditoria,
        private readonly PlanesDeMantenimientoQuery $query,
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
     * @param  array{maquinaria_id: int, umbral_uso: float, descripcion: string}  $datos
     */
    public function crear(Usuario $actor, array $datos, ?string $direccionIp = null): PlanDeMantenimiento
    {
        $this->validarUmbral($datos['umbral_uso']);

        return DB::transaction(function () use ($actor, $datos, $direccionIp): PlanDeMantenimiento {
            $this->validarUnicoPorMaquina($datos['maquinaria_id']);

            $plan = PlanDeMantenimiento::create([
                'maquinaria_id' => $datos['maquinaria_id'],
                'umbral_uso' => $datos['umbral_uso'],
                'descripcion' => $datos['descripcion'],
            ]);

            $this->auditoria->registrarEvento(
                $actor->id,
                'crear_plan_mantenimiento',
                null,
                [
                    'maquinaria_id' => $plan->maquinaria_id,
                    'umbral_uso' => (float) $plan->umbral_uso,
                    'descripcion' => $plan->descripcion,
                ],
                $direccionIp,
                PlanDeMantenimiento::class,
                $plan->id,
            );

            return $plan;
        });
    }

    /**
     * @param  array{maquinaria_id: int, umbral_uso: float, descripcion: string}  $datos
     */
    public function actualizar(Usuario $actor, PlanDeMantenimiento $plan, array $datos, ?string $direccionIp = null): PlanDeMantenimiento
    {
        $this->validarUmbral($datos['umbral_uso']);

        return DB::transaction(function () use ($actor, $plan, $datos, $direccionIp): PlanDeMantenimiento {
            $this->validarUnicoPorMaquina($datos['maquinaria_id'], $plan->id);

            $anterior = [
                'maquinaria_id' => $plan->maquinaria_id,
                'umbral_uso' => (float) $plan->umbral_uso,
                'descripcion' => $plan->descripcion,
            ];

            $plan->update([
                'maquinaria_id' => $datos['maquinaria_id'],
                'umbral_uso' => $datos['umbral_uso'],
                'descripcion' => $datos['descripcion'],
            ]);

            $this->auditoria->registrarEvento(
                $actor->id,
                'actualizar_plan_mantenimiento',
                $anterior,
                [
                    'maquinaria_id' => $datos['maquinaria_id'],
                    'umbral_uso' => (float) $datos['umbral_uso'],
                    'descripcion' => $datos['descripcion'],
                ],
                $direccionIp,
                PlanDeMantenimiento::class,
                $plan->id,
            );

            return $plan;
        });
    }

    public function eliminar(Usuario $actor, PlanDeMantenimiento $plan, ?string $direccionIp = null): void
    {
        DB::transaction(function () use ($actor, $plan, $direccionIp): void {
            if ($plan->maquinaria->estado_operativo === EstadoMaquinaria::Bloqueada->value) {
                throw ValidationException::withMessages([
                    'plan' => 'La máquina tiene un mantenimiento pendiente de ejecución.',
                ]);
            }

            $this->auditoria->registrarEvento(
                $actor->id,
                'eliminar_plan_mantenimiento',
                [
                    'maquinaria_id' => $plan->maquinaria_id,
                    'umbral_uso' => (float) $plan->umbral_uso,
                    'descripcion' => $plan->descripcion,
                ],
                null,
                $direccionIp,
                PlanDeMantenimiento::class,
                $plan->id,
            );

            $plan->delete();
        });
    }

    private function validarUmbral(float $umbralUso): void
    {
        if ($umbralUso <= 0) {
            throw ValidationException::withMessages([
                'umbral_uso' => 'El umbral de uso debe ser mayor a 0.',
            ]);
        }
    }

    private function validarUnicoPorMaquina(int $maquinariaId, ?int $excluyendoId = null): void
    {
        $query = PlanDeMantenimiento::query()->where('maquinaria_id', $maquinariaId);

        if ($excluyendoId !== null) {
            $query->whereKeyNot($excluyendoId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'maquinaria_id' => 'La máquina ya tiene un plan de mantenimiento registrado.',
            ]);
        }
    }
}
