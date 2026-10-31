<?php

namespace App\Modules\Produccion\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use App\Modules\Produccion\Models\ControlDeCalidad;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Models\ParteDiario;
use App\Modules\Produccion\Queries\HojaDeRutaQuery;
use App\Modules\Produccion\Queries\PartesDiariosQuery;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\UsuariosService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionCalidadService
{
    public function __construct(
        private readonly AuditoriaService $auditoria,
        private readonly HojaDeRutaQuery $hojaDeRuta,
        private readonly PartesDiariosQuery $partesDiarios,
        private readonly UsuariosService $usuarios,
    ) {}

    public function puedeGestionar(Usuario $actor): bool
    {
        return $this->usuarios->autorizar($actor, 'produccion.gestionar');
    }

    public function registrar(
        Usuario $actor,
        OrdenDeProduccion $orden,
        ?int $parteDiarioId,
        float $rechazadas,
        ?string $motivo,
        ?string $direccionIp = null,
    ): ControlDeCalidad {
        return DB::transaction(function () use ($actor, $orden, $parteDiarioId, $rechazadas, $motivo, $direccionIp): ControlDeCalidad {
            $orden = OrdenDeProduccion::query()->whereKey($orden->getKey())->lockForUpdate()->firstOrFail();

            if ($orden->estado !== EstadoOrdenProduccion::EnCurso->value) {
                throw ValidationException::withMessages([
                    'estado' => 'La orden debe estar En Curso para registrar un control de calidad.',
                ]);
            }

            if ($rechazadas < 0) {
                throw ValidationException::withMessages([
                    'cantidad_rechazada' => 'La cantidad rechazada no puede ser negativa.',
                ]);
            }

            if ($rechazadas > 0 && ($motivo === null || trim($motivo) === '')) {
                throw ValidationException::withMessages([
                    'motivo' => 'El motivo es obligatorio cuando hay unidades rechazadas.',
                ]);
            }

            if ($parteDiarioId !== null) {
                $parte = ParteDiario::query()
                    ->where('id', $parteDiarioId)
                    ->where('orden_de_produccion_id', $orden->id)
                    ->first();

                if ($parte === null) {
                    throw ValidationException::withMessages([
                        'parte_diario_id' => 'El parte no pertenece a la orden.',
                    ]);
                }

                $base = (float) $parte->cantidad_conformada;
            } else {
                $ultimaEtapaId = $this->hojaDeRuta->ultimaEtapaPorArticulo([$orden->articulo_id])[$orden->articulo_id] ?? null;

                if ($ultimaEtapaId === null) {
                    throw ValidationException::withMessages([
                        'orden' => 'El producto no tiene hoja de ruta.',
                    ]);
                }

                $conformadoPorEtapa = $this->partesDiarios->conformadoPorEtapa($orden->id);
                $base = $conformadoPorEtapa[$ultimaEtapaId] ?? 0.0;
            }

            if (round($rechazadas - $base, 2) > 0) {
                throw ValidationException::withMessages([
                    'cantidad_rechazada' => "Las rechazadas no pueden superar el conformado de {$base} unidades.",
                ]);
            }

            $control = ControlDeCalidad::create([
                'orden_de_produccion_id' => $orden->id,
                'parte_diario_id' => $parteDiarioId,
                'fecha' => now()->toDateString(),
                'cantidad_aprobada' => round($base - $rechazadas, 2),
                'cantidad_rechazada' => $rechazadas,
                'motivo_rechazo' => $motivo,
            ]);

            $this->auditoria->registrarEvento(
                $actor->id,
                'registrar_control_calidad',
                null,
                [
                    'parte_diario_id' => $parteDiarioId,
                    'cantidad_aprobada' => $control->cantidad_aprobada,
                    'cantidad_rechazada' => $control->cantidad_rechazada,
                    'motivo_rechazo' => $motivo,
                ],
                $direccionIp,
                ControlDeCalidad::class,
                $control->id,
            );

            return $control;
        });
    }
}
