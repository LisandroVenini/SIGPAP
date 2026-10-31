<?php

namespace App\Modules\Produccion\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Inventario\Services\GestionReservasService;
use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use App\Modules\Produccion\Models\AsignacionDeEtapa;
use App\Modules\Produccion\Models\Maquinaria;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Models\ParteDiario;
use App\Modules\Produccion\Queries\PartesDiariosQuery;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\UsuariosService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionPartesDiariosService
{
    public function __construct(
        private readonly AuditoriaService $auditoria,
        private readonly GestionRecetasService $recetas,
        private readonly GestionReservasService $reservas,
        private readonly PartesDiariosQuery $query,
        private readonly UsuariosService $usuarios,
    ) {}

    public function puedeGestionar(Usuario $actor): bool
    {
        return $this->usuarios->autorizar($actor, 'produccion.gestionar');
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
        return DB::transaction(function () use ($actor, $orden, $etapaId, $maquinariaId, $fecha, $cantidad, $observaciones, $direccionIp): ParteDiario {
            $orden = OrdenDeProduccion::query()->whereKey($orden->getKey())->lockForUpdate()->firstOrFail();

            if ($orden->estado !== EstadoOrdenProduccion::EnCurso->value) {
                throw ValidationException::withMessages([
                    'estado' => 'La orden debe estar En Curso para registrar un parte diario.',
                ]);
            }

            $asignaciones = AsignacionDeEtapa::query()
                ->where('articulo_id', $orden->articulo_id)
                ->orderBy('id')
                ->orderBy('etapa_id')
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
            $restante = round((float) $orden->cantidad_requerida - $conformadoEnEtapa, 2);

            if (round($cantidad - $restante, 2) > 0) {
                throw ValidationException::withMessages([
                    'cantidad' => "La cantidad no puede superar el restante de {$restante} unidades.",
                ]);
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
                $maquinaria->desgaste_acumulado = round((float) $maquinaria->desgaste_acumulado + $cantidad, 2);
                $maquinaria->save();
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
