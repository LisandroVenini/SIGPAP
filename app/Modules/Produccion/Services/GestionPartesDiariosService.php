<?php

namespace App\Modules\Produccion\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Inventario\Services\GestionArticulosService;
use App\Modules\Inventario\Services\GestionReservasService;
use App\Modules\Produccion\Enums\EstadoMaquinaria;
use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use App\Modules\Produccion\Jobs\RecalcularTiempoProduccionJob;
use App\Modules\Produccion\Models\AsignacionDeEtapa;
use App\Modules\Produccion\Models\Maquinaria;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Models\ParteDiario;
use App\Modules\Produccion\Queries\AvancePorEtapasQuery;
use App\Modules\Produccion\Queries\CalidadQuery;
use App\Modules\Produccion\Queries\PartesDiariosQuery;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\UsuariosService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionPartesDiariosService
{
    public function __construct(
        private readonly AuditoriaService $auditoria,
        private readonly GestionMantenimientosService $mantenimientos,
        private readonly GestionRecetasService $recetas,
        private readonly GestionReservasService $reservas,
        private readonly PartesDiariosQuery $query,
        private readonly UsuariosService $usuarios,
        private readonly AvancePorEtapasQuery $avancePorEtapas,
        private readonly GestionArticulosService $articulos,
        private readonly CalidadQuery $calidad,
    ) {}

    public function puedeGestionar(Usuario $actor): bool
    {
        return $this->usuarios->autorizar($actor, 'produccion.gestionar');
    }

    /**
     * Orders in progress with everything the daily-part panel needs to render.
     *
     * @return array<int, array{id:int, producto: array<string, mixed>|null, cantidad_requerida:float, etapas: array<int, array{etapa_id:int, nombre:string, producida:float, rechazadas:float, faltante:float}>, maquinarias: array<int, array{id:int, nombre:string}>, partes_hoy: array<int, mixed>, partes_historial: array<int, mixed>, controles: array<int, array{id:int, fecha:string|null, etapa:string|null, aprobada:float, rechazada:float, motivo:string|null}>}>
     */
    public function ordenesEnCursoParaCarga(string $fecha): array
    {
        $ordenes = OrdenDeProduccion::query()
            ->where('estado', EstadoOrdenProduccion::EnCurso->value)
            ->with('maquinarias')
            ->orderBy('fecha_entrega')
            ->orderBy('id')
            ->get();

        if ($ordenes->isEmpty()) {
            return [];
        }

        $resumenes = $this->articulos->resumenesDeArticulos(
            $ordenes->pluck('articulo_id')->unique()->filter()->all(),
        );
        $etapasPorOrden = $this->avancePorEtapas->porOrdenes(
            $ordenes->pluck('articulo_id', 'id')->all(),
            $ordenes->pluck('cantidad_requerida', 'id')
                ->map(fn ($cantidad): float => (float) $cantidad)
                ->all(),
        );
        $ordenIds = $ordenes->pluck('id')->all();
        $partesPorOrden = $this->query->porOrdenes($ordenIds, $fecha);
        $historialPorOrden = $this->query->porOrdenes($ordenIds);
        $controlesPorOrden = $this->calidad->controlesPorOrdenes($ordenIds);

        return $ordenes->map(fn (OrdenDeProduccion $orden): array => [
            'id' => $orden->id,
            'producto' => $resumenes[$orden->articulo_id] ?? null,
            'cantidad_requerida' => (float) $orden->cantidad_requerida,
            'etapas' => $etapasPorOrden[$orden->id] ?? [],
            'maquinarias' => $orden->maquinarias
                ->sortBy('nombre')
                ->map(fn (Maquinaria $maquinaria): array => [
                    'id' => $maquinaria->id,
                    'nombre' => $maquinaria->nombre,
                ])
                ->values()
                ->all(),
            'partes_hoy' => $partesPorOrden[$orden->id] ?? [],
            'partes_historial' => $historialPorOrden[$orden->id] ?? [],
            'controles' => $controlesPorOrden[$orden->id] ?? [],
        ])->values()->all();
    }

    public function registrar(
        Usuario $actor,
        OrdenDeProduccion $orden,
        int $etapaId,
        ?int $maquinariaId,
        string $fecha,
        float $cantidad,
        ?string $observaciones,
        ?string $direccionIp = null,
    ): ParteDiario {
        $parte = DB::transaction(function () use ($actor, $orden, $etapaId, $maquinariaId, $fecha, $cantidad, $observaciones, $direccionIp): ParteDiario {
            $orden = OrdenDeProduccion::query()->whereKey($orden->getKey())->lockForUpdate()->firstOrFail();

            if ($orden->estado !== EstadoOrdenProduccion::EnCurso->value) {
                throw ValidationException::withMessages([
                    'estado' => 'La orden debe estar En Curso para registrar un parte diario.',
                ]);
            }

            $asignaciones = AsignacionDeEtapa::query()
                ->where('articulo_id', $orden->articulo_id)
                ->ordenadas()
                ->get()
                ->keyBy('etapa_id');

            if (! $asignaciones->has($etapaId)) {
                throw ValidationException::withMessages([
                    'etapa' => 'La etapa no pertenece a la hoja de ruta del producto.',
                ]);
            }

            if ($maquinariaId !== null) {
                $asignada = $orden->maquinarias()
                    ->where('maquinarias.id', $maquinariaId)
                    ->exists();

                if (! $asignada) {
                    throw ValidationException::withMessages([
                        'maquinaria' => 'La maquinaria no está asignada a la orden.',
                    ]);
                }
            }

            if ($cantidad <= 0) {
                throw ValidationException::withMessages([
                    'cantidad' => 'La cantidad debe ser mayor a 0.',
                ]);
            }

            $conformadoPorEtapa = $this->query->conformadoPorEtapa($orden->id);
            $conformadoEnEtapa = $conformadoPorEtapa[$etapaId] ?? 0.0;
            $rechazadasPorEtapa = $this->query->rechazadasPorEtapa($orden->id);
            $rechazadasEnEtapa = $rechazadasPorEtapa[$etapaId] ?? 0.0;
            $restante = round((float) $orden->cantidad_requerida + $rechazadasEnEtapa - $conformadoEnEtapa, 2);

            if (round($cantidad - $restante, 2) > 0) {
                throw ValidationException::withMessages([
                    'cantidad' => "La cantidad no puede superar el restante de {$restante} unidades.",
                ]);
            }

            $etapasOrdenadas = $asignaciones->keys()->values()->all();
            $posicion = array_search($etapaId, $etapasOrdenadas, true);

            if ($posicion !== false && $posicion > 0) {
                $etapaAnterior = $etapasOrdenadas[$posicion - 1];
                $conformadoAnterior = $conformadoPorEtapa[$etapaAnterior] ?? 0.0;

                if (round(($conformadoEnEtapa + $cantidad) - $conformadoAnterior, 2) > 0) {
                    throw ValidationException::withMessages([
                        'etapa' => "La etapa anterior tiene {$conformadoAnterior} unidades conformadas: no se pueden registrar más de esas en esta etapa.",
                    ]);
                }
            }

            $parte = ParteDiario::query()
                ->where('orden_de_produccion_id', $orden->id)
                ->where('etapa_id', $etapaId)
                ->whereDate('fecha', $fecha)
                ->first();

            $esPrimeraEtapa = (int) $asignaciones->keys()->first() === $etapaId;

            if ($parte === null) {
                $parte = new ParteDiario([
                    'orden_de_produccion_id' => $orden->id,
                    'etapa_id' => $etapaId,
                    'maquinaria_id' => $maquinariaId,
                    'fecha' => $fecha,
                    'cantidad_conformada' => 0,
                    'observaciones' => $observaciones,
                ]);
            }

            if ($esPrimeraEtapa) {
                $necesidades = $this->calcularNecesidades($orden->articulo_id, $cantidad);

                if ($necesidades !== []) {
                    $this->reservas->consumir($orden->id, $necesidades);
                }
            }

            $parte->cantidad_conformada = round((float) $parte->cantidad_conformada + $cantidad, 2);
            $parte->observaciones = $observaciones ?? $parte->observaciones;
            $parte->save();

            if ($maquinariaId !== null) {
                $maquinaria = Maquinaria::query()->whereKey($maquinariaId)->lockForUpdate()->firstOrFail();

                if ($maquinaria->estado_operativo !== EstadoMaquinaria::Disponible->value) {
                    throw ValidationException::withMessages([
                        'maquinaria' => 'La maquinaria no está disponible: no se puede registrar producción con ella.',
                    ]);
                }

                $maquinaria->desgaste_acumulado = round((float) $maquinaria->desgaste_acumulado + $cantidad, 2);
                $maquinaria->save();

                $this->mantenimientos->evaluarBloqueo($actor, $maquinaria, $direccionIp);
            }

            $this->auditoria->registrarEvento(
                $actor->id,
                'registrar_parte_diario',
                null,
                [
                    'etapa_id' => $etapaId,
                    'fecha' => $fecha,
                    'cantidad' => $cantidad,
                    'consumido' => $esPrimeraEtapa,
                ],
                $direccionIp,
                ParteDiario::class,
                $parte->id,
            );

            return $parte;
        });

        // CU-PRED-02: recalculate the projected finish in the background, so the
        // operator is never blocked. Dispatched after commit on purpose.
        RecalcularTiempoProduccionJob::dispatch(
            $parte->orden_de_produccion_id,
            $etapaId,
            $parte->fecha->toDateString(),
        );

        return $parte;
    }

    /**
     * @return array<int, float>
     */
    private function calcularNecesidades(int $articuloId, float $cantidad): array
    {
        $detalle = $this->recetas->detalle($articuloId);
        $componentes = $detalle['componentes'] ?? [];

        $necesidades = [];

        foreach ($componentes as $componente) {
            $necesidades[$componente['articulo_id']] = round($cantidad * $componente['cantidad'], 2);
        }

        return $necesidades;
    }
}
