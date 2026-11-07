<?php

namespace App\Modules\Produccion\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Inventario\Services\GestionReservasService;
use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use App\Modules\Produccion\Models\ControlDeCalidad;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Queries\HojaDeRutaQuery;
use App\Modules\Produccion\Queries\PartesDiariosQuery;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\UsuariosService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionCierreService
{
    public function __construct(
        private readonly AuditoriaService $auditoria,
        private readonly GestionRecetasService $recetas,
        private readonly GestionReservasService $reservas,
        private readonly HojaDeRutaQuery $hojaDeRuta,
        private readonly PartesDiariosQuery $partesDiarios,
        private readonly UsuariosService $usuarios,
    ) {}

    public function puedeGestionar(Usuario $actor): bool
    {
        return $this->usuarios->autorizar($actor, 'produccion.gestionar');
    }

    public function cerrar(
        Usuario $actor,
        OrdenDeProduccion $orden,
        float $unidadesFinales,
        ?string $direccionIp = null,
    ): void {
        DB::transaction(function () use ($actor, $orden, $unidadesFinales, $direccionIp): void {
            $orden = OrdenDeProduccion::query()->whereKey($orden->getKey())->lockForUpdate()->firstOrFail();

            if ($orden->estado !== EstadoOrdenProduccion::EnCurso->value) {
                throw ValidationException::withMessages([
                    'estado' => 'La orden debe estar En Curso para cerrarse.',
                ]);
            }

            $ultimaEtapaId = $this->hojaDeRuta->ultimaEtapaPorArticulo([$orden->articulo_id])[$orden->articulo_id] ?? null;
            $aprobadas = $this->aprobadasParaCierre($orden, $ultimaEtapaId);

            if ($aprobadas === null) {
                throw ValidationException::withMessages([
                    'calidad' => 'Registrá el control de calidad de la última etapa o de la orden.',
                ]);
            }

            $requerida = (float) $orden->cantidad_requerida;

            if (round($unidadesFinales - $requerida, 2) !== 0.0) {
                throw ValidationException::withMessages([
                    'unidades_finales' => 'Las unidades finales deben ser iguales a la cantidad requerida.',
                ]);
            }

            if (round($aprobadas - $requerida, 2) < 0.0) {
                $faltan = round($requerida - $aprobadas, 2);

                throw ValidationException::withMessages([
                    'unidades_finales' => "Faltan {$faltan} unidades aprobadas para cerrar la orden.",
                ]);
            }

            $orden->estado = EstadoOrdenProduccion::Cerrada->value;
            $orden->cantidad_producida = $unidadesFinales;
            $orden->fecha_fin = now()->toDateString();
            $orden->save();

            $this->auditoria->registrarEvento(
                $actor->id,
                'cerrar_orden_produccion',
                [
                    'estado' => EstadoOrdenProduccion::EnCurso->value,
                    'cantidad_producida' => (float) $orden->cantidad_producida,
                ],
                [
                    'estado' => EstadoOrdenProduccion::Cerrada->value,
                    'cantidad_producida' => $unidadesFinales,
                    'unidades_finales' => $unidadesFinales,
                ],
                $direccionIp,
                OrdenDeProduccion::class,
                $orden->id,
            );
        });
    }

    public function posponer(
        Usuario $actor,
        OrdenDeProduccion $orden,
        ?string $fechaPrevista,
        bool $liberarReservas,
        ?string $motivo,
        ?string $direccionIp = null,
    ): void {
        DB::transaction(function () use ($actor, $orden, $fechaPrevista, $liberarReservas, $motivo, $direccionIp): void {
            $orden = OrdenDeProduccion::query()->whereKey($orden->getKey())->lockForUpdate()->firstOrFail();

            $estadosPermitidos = [
                EstadoOrdenProduccion::Pendiente->value,
                EstadoOrdenProduccion::PendientePorInsumos->value,
                EstadoOrdenProduccion::Asignada->value,
                EstadoOrdenProduccion::EnCurso->value,
            ];

            if (! in_array($orden->estado, $estadosPermitidos, true)) {
                throw ValidationException::withMessages([
                    'estado' => 'La orden no puede posponerse en el estado actual.',
                ]);
            }

            if ($liberarReservas) {
                $this->reservas->liberar($orden->id);
            }

            $anterior = [
                'estado' => $orden->estado,
                'fecha_prevista_inicio' => $orden->fecha_prevista_inicio?->toDateString(),
            ];

            $orden->estado = EstadoOrdenProduccion::Pospuesta->value;
            $orden->fecha_prevista_inicio = $fechaPrevista;
            $orden->save();

            $this->auditoria->registrarEvento(
                $actor->id,
                'posponer_orden_produccion',
                $anterior,
                [
                    'estado' => $orden->estado,
                    'liberar_reservas' => $liberarReservas,
                    'fecha_prevista_inicio' => $fechaPrevista,
                    'motivo' => $motivo,
                ],
                $direccionIp,
                OrdenDeProduccion::class,
                $orden->id,
            );
        });
    }

    /**
     * @return array{estado: string, faltantes: array<int, float>}
     */
    public function reanudar(
        Usuario $actor,
        OrdenDeProduccion $orden,
        ?string $direccionIp = null,
    ): array {
        return DB::transaction(function () use ($actor, $orden, $direccionIp): array {
            $orden = OrdenDeProduccion::query()->whereKey($orden->getKey())->lockForUpdate()->firstOrFail();

            if ($orden->estado !== EstadoOrdenProduccion::Pospuesta->value) {
                throw ValidationException::withMessages([
                    'estado' => 'La orden solo puede reanudarse estando Pospuesta.',
                ]);
            }

            $primeraEtapaId = $this->hojaDeRuta->primeraEtapaPorArticulo([$orden->articulo_id])[$orden->articulo_id] ?? null;
            $conformadoPrimeraEtapa = $primeraEtapaId !== null
                ? ($this->partesDiarios->conformadoPorEtapa($orden->id)[$primeraEtapaId] ?? 0.0)
                : 0.0;

            $cantidadRestante = round((float) $orden->cantidad_requerida - $conformadoPrimeraEtapa, 2);
            $faltantes = [];

            if ($cantidadRestante > 0) {
                $necesidades = $this->calcularNecesidades($orden->articulo_id, $cantidadRestante);

                if (! $this->reservas->cubierta($orden->id, $necesidades)) {
                    $reserva = $this->reservas->reemplazar($orden->id, $necesidades);

                    if (! $reserva['cubierto']) {
                        $orden->estado = EstadoOrdenProduccion::PendientePorInsumos->value;
                        $orden->save();

                        $this->auditoria->registrarEvento(
                            $actor->id,
                            'reanudar_orden_produccion',
                            ['estado' => EstadoOrdenProduccion::Pospuesta->value],
                            [
                                'estado' => $orden->estado,
                                'faltantes' => $reserva['faltantes'],
                            ],
                            $direccionIp,
                            OrdenDeProduccion::class,
                            $orden->id,
                        );

                        return [
                            'estado' => $orden->estado,
                            'faltantes' => $reserva['faltantes'],
                        ];
                    }
                }
            }

            $nuevoEstado = $this->determinarEstadoReanudacion($orden);
            $anterior = ['estado' => $orden->estado];
            $orden->estado = $nuevoEstado->value;
            $orden->save();

            $this->auditoria->registrarEvento(
                $actor->id,
                'reanudar_orden_produccion',
                $anterior,
                ['estado' => $nuevoEstado->value, 'faltantes' => []],
                $direccionIp,
                OrdenDeProduccion::class,
                $orden->id,
            );

            return [
                'estado' => $nuevoEstado->value,
                'faltantes' => [],
            ];
        });
    }

    private function aprobadasParaCierre(OrdenDeProduccion $orden, ?int $ultimaEtapaId): ?float
    {
        $controlDeOrden = ControlDeCalidad::query()
            ->where('orden_de_produccion_id', $orden->id)
            ->whereNull('parte_diario_id')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->first();

        if ($controlDeOrden !== null) {
            return (float) $controlDeOrden->cantidad_aprobada;
        }

        if ($ultimaEtapaId === null) {
            return null;
        }

        $tieneControlDeParte = ControlDeCalidad::query()
            ->where('orden_de_produccion_id', $orden->id)
            ->whereNotNull('parte_diario_id')
            ->whereExists(function ($query) use ($ultimaEtapaId): void {
                $query->selectRaw('1')
                    ->from('partes_diarios')
                    ->whereColumn('partes_diarios.id', 'controles_de_calidad.parte_diario_id')
                    ->where('partes_diarios.etapa_id', $ultimaEtapaId);
            })
            ->exists();

        if (! $tieneControlDeParte) {
            return null;
        }

        $conformado = $this->partesDiarios->conformadoPorEtapa($orden->id)[$ultimaEtapaId] ?? 0.0;
        $rechazadas = $this->partesDiarios->rechazadasPorEtapa($orden->id)[$ultimaEtapaId] ?? 0.0;

        return round($conformado - $rechazadas, 2);
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

    private function determinarEstadoReanudacion(OrdenDeProduccion $orden): EstadoOrdenProduccion
    {
        if ($orden->fecha_inicio !== null) {
            return EstadoOrdenProduccion::EnCurso;
        }

        $orden->loadCount(['operarios', 'maquinarias']);

        if ($orden->operarios_count > 0 && $orden->maquinarias_count > 0) {
            return EstadoOrdenProduccion::Asignada;
        }

        return EstadoOrdenProduccion::Pendiente;
    }
}
