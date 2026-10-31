<?php

namespace App\Modules\Produccion\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Inventario\Services\GestionReservasService;
use App\Modules\Produccion\Enums\EstadoMaquinaria;
use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use App\Modules\Produccion\Models\Maquinaria;
use App\Modules\Produccion\Models\Operario;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\UsuariosService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionRecursosDeOrdenService
{
    public function __construct(
        private readonly AuditoriaService $auditoria,
        private readonly GestionRecetasService $recetas,
        private readonly GestionReservasService $reservas,
        private readonly UsuariosService $usuarios,
    ) {}

    public function puedeGestionar(Usuario $actor): bool
    {
        return $this->usuarios->autorizar($actor, 'produccion.gestionar');
    }

    /**
     * @return array{operarios: array<int, array{id:int, nombre:string}>, maquinarias: array<int, array{id:int, nombre:string, estado:string, seleccionable:bool}>}
     */
    public function recursosDisponibles(): array
    {
        $operarios = Operario::query()
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->get()
            ->mapWithKeys(fn (Operario $operario): array => [
                $operario->id => [
                    'id' => $operario->id,
                    'nombre' => "{$operario->apellido}, {$operario->nombre}",
                ],
            ])
            ->values()
            ->all();

        $maquinarias = Maquinaria::query()
            ->orderBy('nombre')
            ->get()
            ->mapWithKeys(fn (Maquinaria $maquinaria): array => [
                $maquinaria->id => [
                    'id' => $maquinaria->id,
                    'nombre' => $maquinaria->nombre,
                    'estado' => $maquinaria->estado_operativo,
                    'seleccionable' => $maquinaria->estado_operativo === EstadoMaquinaria::Disponible->value,
                ],
            ])
            ->all();

        return [
            'operarios' => $operarios,
            'maquinarias' => $maquinarias,
        ];
    }

    /**
     * @param  array<int, int>  $operarioIds
     * @param  array<int, int>  $maquinariaIds
     */
    public function asignar(
        Usuario $actor,
        OrdenDeProduccion $orden,
        array $operarioIds,
        array $maquinariaIds,
        ?string $direccionIp = null,
    ): void {
        DB::transaction(function () use ($actor, $orden, $operarioIds, $maquinariaIds, $direccionIp): void {
            $orden = OrdenDeProduccion::query()->whereKey($orden->getKey())->lockForUpdate()->firstOrFail();

            $estadosPermitidos = [
                EstadoOrdenProduccion::Pendiente->value,
                EstadoOrdenProduccion::PendientePorInsumos->value,
                EstadoOrdenProduccion::Asignada->value,
            ];

            if (! in_array($orden->estado, $estadosPermitidos, true)) {
                throw ValidationException::withMessages([
                    'estado' => 'La orden no puede gestionarse en el estado actual.',
                ]);
            }

            $maquinarias = Maquinaria::query()->whereKey($maquinariaIds)->get()->keyBy('id');
            $errores = [];

            foreach ($maquinariaIds as $maquinariaId) {
                $maquinaria = $maquinarias->get($maquinariaId);

                if ($maquinaria === null) {
                    $errores[] = 'La maquinaria seleccionada no existe.';

                    continue;
                }

                if ($maquinaria->estado_operativo !== EstadoMaquinaria::Disponible->value) {
                    $errores[] = "{$maquinaria->nombre} no está disponible ({$maquinaria->estado_operativo}).";
                }
            }

            if ($errores !== []) {
                throw ValidationException::withMessages([
                    'maquinarias' => $errores,
                ]);
            }

            $orden->operarios()->sync($operarioIds);
            $orden->maquinarias()->sync($maquinariaIds);
            $orden->load(['operarios', 'maquinarias']);

            if ($operarioIds !== [] || $maquinariaIds !== []) {
                $nuevoEstado = EstadoOrdenProduccion::Asignada->value;
            } else {
                $detalle = $this->recetas->detalle($orden->articulo_id);
                $componentes = $detalle['componentes'] ?? [];
                $necesidades = [];

                foreach ($componentes as $componente) {
                    $necesidades[$componente['articulo_id']] = round($orden->cantidad_requerida * $componente['cantidad'], 2);
                }

                $nuevoEstado = $this->reservas->cubierta($orden->id, $necesidades)
                    ? EstadoOrdenProduccion::Pendiente->value
                    : EstadoOrdenProduccion::PendientePorInsumos->value;
            }

            $orden->estado = $nuevoEstado;
            $orden->save();

            $this->auditoria->registrarEvento(
                $actor->id,
                'asignar_recursos_orden',
                null,
                [
                    'operarios' => $orden->operarios->pluck('id')->all(),
                    'maquinarias' => $orden->maquinarias->pluck('id')->all(),
                    'estado' => $nuevoEstado,
                ],
                $direccionIp,
                OrdenDeProduccion::class,
                $orden->id,
            );
        });
    }

    public function iniciar(
        Usuario $actor,
        OrdenDeProduccion $orden,
        ?string $direccionIp = null,
    ): void {
        DB::transaction(function () use ($actor, $orden, $direccionIp): void {
            $orden = OrdenDeProduccion::query()->whereKey($orden->getKey())->lockForUpdate()->firstOrFail();

            if ($orden->estado !== EstadoOrdenProduccion::Asignada->value) {
                throw ValidationException::withMessages([
                    'estado' => 'La orden solo puede iniciarse estando Asignada.',
                ]);
            }

            $orden->loadCount(['operarios', 'maquinarias']);

            if ($orden->operarios_count === 0 || $orden->maquinarias_count === 0) {
                throw ValidationException::withMessages([
                    'recursos' => 'La orden debe tener al menos un operario y una maquinaria asignados.',
                ]);
            }

            $orden->estado = EstadoOrdenProduccion::EnCurso->value;
            $orden->fecha_inicio = now();
            $orden->save();

            $this->auditoria->registrarEvento(
                $actor->id,
                'iniciar_orden_produccion',
                ['estado' => EstadoOrdenProduccion::Asignada->value],
                ['estado' => EstadoOrdenProduccion::EnCurso->value],
                $direccionIp,
                OrdenDeProduccion::class,
                $orden->id,
            );
        });
    }
}
