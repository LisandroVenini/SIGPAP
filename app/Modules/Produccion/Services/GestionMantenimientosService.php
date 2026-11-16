<?php

namespace App\Modules\Produccion\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Produccion\Enums\EstadoMaquinaria;
use App\Modules\Produccion\Models\IntervencionDeMantenimiento;
use App\Modules\Produccion\Models\Maquinaria;
use App\Modules\Produccion\Queries\DashboardMantenimientosQuery;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionMantenimientosService
{
    public function __construct(
        private readonly AuditoriaService $auditoria,
        private readonly DashboardMantenimientosQuery $dashboard,
    ) {}

    /**
     * Maintenance indicators for the dashboard.
     *
     * @return array{maquinas_bloqueadas: int, planes: int}
     */
    public function resumenDashboard(): array
    {
        return $this->dashboard->resumen();
    }

    /**
     * Blocked machines ordered by accumulated wear, most worn first.
     *
     * @return array<int, array{id: int, nombre: string, desgaste: float}>
     */
    public function maquinasBloqueadas(int $limite): array
    {
        return $this->dashboard->bloqueadas($limite);
    }

    public function registrar(
        Usuario $actor,
        Maquinaria $maquinaria,
        string $fecha,
        string $tipo,
        string $responsable,
        ?string $observaciones,
        ?string $direccionIp = null,
    ): IntervencionDeMantenimiento {
        return DB::transaction(function () use ($actor, $maquinaria, $fecha, $tipo, $responsable, $observaciones, $direccionIp): IntervencionDeMantenimiento {
            $maquinaria = Maquinaria::query()->whereKey($maquinaria->getKey())->lockForUpdate()->firstOrFail();

            $intervencion = $maquinaria->intervencionesDeMantenimiento()->create([
                'fecha' => $fecha,
                'tipo' => $tipo,
                'responsable' => $responsable,
                'observaciones' => $observaciones,
            ]);

            $maquinaria->desgaste_acumulado = 0;
            $maquinaria->estado_operativo = EstadoMaquinaria::Disponible->value;
            $maquinaria->save();

            $this->auditoria->registrarEvento(
                $actor->id,
                'registrar_intervencion_mantenimiento',
                null,
                [
                    'maquinaria_id' => $maquinaria->id,
                    'fecha' => $fecha,
                    'tipo' => $tipo,
                    'responsable' => $responsable,
                    'observaciones' => $observaciones,
                ],
                $direccionIp,
                IntervencionDeMantenimiento::class,
                $intervencion->id,
            );

            return $intervencion;
        });
    }

    public function actualizar(
        Usuario $actor,
        Maquinaria $maquinaria,
        IntervencionDeMantenimiento $intervencion,
        string $fecha,
        string $tipo,
        string $responsable,
        ?string $observaciones,
        ?string $direccionIp = null,
    ): IntervencionDeMantenimiento {
        return DB::transaction(function () use ($actor, $maquinaria, $intervencion, $fecha, $tipo, $responsable, $observaciones, $direccionIp): IntervencionDeMantenimiento {
            $this->asegurarPertenencia($maquinaria, $intervencion);

            $anterior = [
                'fecha' => $intervencion->fecha?->toDateString(),
                'tipo' => $intervencion->tipo,
                'responsable' => $intervencion->responsable,
                'observaciones' => $intervencion->observaciones,
            ];

            $intervencion->update([
                'fecha' => $fecha,
                'tipo' => $tipo,
                'responsable' => $responsable,
                'observaciones' => $observaciones,
            ]);

            $this->auditoria->registrarEvento(
                $actor->id,
                'actualizar_intervencion_mantenimiento',
                $anterior,
                [
                    'fecha' => $fecha,
                    'tipo' => $tipo,
                    'responsable' => $responsable,
                    'observaciones' => $observaciones,
                ],
                $direccionIp,
                IntervencionDeMantenimiento::class,
                $intervencion->id,
            );

            return $intervencion;
        });
    }

    public function eliminar(
        Usuario $actor,
        Maquinaria $maquinaria,
        IntervencionDeMantenimiento $intervencion,
        ?string $direccionIp = null,
    ): void {
        DB::transaction(function () use ($actor, $maquinaria, $intervencion, $direccionIp): void {
            $this->asegurarPertenencia($maquinaria, $intervencion);

            $this->auditoria->registrarEvento(
                $actor->id,
                'eliminar_intervencion_mantenimiento',
                [
                    'fecha' => $intervencion->fecha?->toDateString(),
                    'tipo' => $intervencion->tipo,
                    'responsable' => $intervencion->responsable,
                    'observaciones' => $intervencion->observaciones,
                ],
                null,
                $direccionIp,
                IntervencionDeMantenimiento::class,
                $intervencion->id,
            );

            $intervencion->delete();
        });
    }

    private function asegurarPertenencia(Maquinaria $maquinaria, IntervencionDeMantenimiento $intervencion): void
    {
        if ((int) $intervencion->maquinaria_id !== (int) $maquinaria->getKey()) {
            throw ValidationException::withMessages([
                'intervencion' => 'La intervención no pertenece a la maquinaria indicada.',
            ]);
        }
    }

    public function evaluarBloqueo(Usuario $actor, Maquinaria $maquinaria, ?string $direccionIp = null): bool
    {
        if ($maquinaria->estado_operativo !== EstadoMaquinaria::Disponible->value) {
            return false;
        }

        $plan = $maquinaria->planesDeMantenimiento()->first();
        if ($plan === null) {
            return false;
        }

        if ((float) $maquinaria->desgaste_acumulado < (float) $plan->umbral_uso) {
            return false;
        }

        $maquinaria->estado_operativo = EstadoMaquinaria::Bloqueada->value;
        $maquinaria->save();

        $this->auditoria->registrarEvento(
            $actor->id,
            'bloquear_maquinaria_por_uso',
            ['estado_operativo' => EstadoMaquinaria::Disponible->value],
            ['estado_operativo' => EstadoMaquinaria::Bloqueada->value],
            $direccionIp,
            Maquinaria::class,
            $maquinaria->id,
        );

        return true;
    }

    /**
     * @return Collection<int, array{id:int, maquinaria_id:int, maquinaria:string, fecha:string, tipo:string, responsable:string, observaciones:string|null}>
     */
    public function intervenciones(?int $maquinariaId = null): Collection
    {
        return IntervencionDeMantenimiento::query()
            ->with('maquinaria')
            ->ordenadas()
            ->when($maquinariaId !== null, fn ($query) => $query->deMaquinaria($maquinariaId))
            ->get()
            ->map(fn (IntervencionDeMantenimiento $intervencion): array => [
                'id' => $intervencion->id,
                'maquinaria_id' => $intervencion->maquinaria_id,
                'maquinaria' => $intervencion->maquinaria->nombre,
                'fecha' => $intervencion->fecha->toDateString(),
                'tipo' => $intervencion->tipo,
                'responsable' => $intervencion->responsable,
                'observaciones' => $intervencion->observaciones,
            ]);
    }
}
