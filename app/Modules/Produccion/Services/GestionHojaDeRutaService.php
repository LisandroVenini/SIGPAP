<?php

namespace App\Modules\Produccion\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Inventario\Services\GestionArticulosService;
use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use App\Modules\Produccion\Models\AsignacionDeEtapa;
use App\Modules\Produccion\Models\Etapa;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Queries\HojaDeRutaQuery;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionHojaDeRutaService
{
    public function __construct(
        private readonly AuditoriaService $auditoria,
        private readonly GestionArticulosService $articulos,
        private readonly HojaDeRutaQuery $query,
    ) {}

    /**
     * @param  array{buscar?: string|null}  $filtros
     * @return Collection<int, array{id: int, codigo_sku: string, descripcion: string, unidad: string|null, etapas: int}>
     */
    public function productos(array $filtros = []): Collection
    {
        $productos = $this->articulos->productosTerminadosFabricados($filtros['buscar'] ?? null);

        $conteos = $this->query->contarEtapasPorArticulo($productos->pluck('id'));

        return $productos->map(fn (array $producto): array => $producto + [
            'etapas' => $conteos[$producto['id']] ?? 0,
        ]);
    }

    /**
     * @return array{producto: array{id: int, codigo_sku: string, descripcion: string, unidad: string|null}, asignadas: array<int, array{etapa_id: int, etapa: string|null, tiempo_por_unidad: float}>, disponibles: array<int, array{id: int, nombre: string}>}|null
     */
    public function detalle(int $articuloId): ?array
    {
        $producto = $this->articulos->productoTerminadoFabricado($articuloId);

        if ($producto === null) {
            return null;
        }

        $asignadas = AsignacionDeEtapa::query()
            ->with('etapa')
            ->where('articulo_id', $articuloId)
            ->get()
            ->map(fn (AsignacionDeEtapa $asignacion): array => [
                'etapa_id' => $asignacion->etapa_id,
                'etapa' => $asignacion->etapa?->nombre,
                'tiempo_por_unidad' => (float) $asignacion->tiempo_por_unidad,
            ])
            ->sortBy('etapa')
            ->values();

        $disponibles = Etapa::query()
            ->whereNotIn('id', $asignadas->pluck('etapa_id'))
            ->orderBy('nombre')
            ->get(['id', 'nombre'])
            ->map(fn (Etapa $etapa): array => ['id' => $etapa->id, 'nombre' => $etapa->nombre]);

        return [
            'producto' => $producto,
            'asignadas' => $asignadas->all(),
            'disponibles' => $disponibles->all(),
        ];
    }

    public function asignar(
        Usuario $actor,
        int $articuloId,
        int $etapaId,
        float $tiempoPorUnidad,
        ?string $direccionIp = null,
    ): void {
        if ($this->articulos->productoTerminadoFabricado($articuloId) === null) {
            throw ValidationException::withMessages([
                'articulo' => 'Solo los productos terminados de origen Fabricado tienen hoja de ruta.',
            ]);
        }

        if (AsignacionDeEtapa::query()->where('articulo_id', $articuloId)->where('etapa_id', $etapaId)->exists()) {
            throw ValidationException::withMessages([
                'etapa_id' => 'La etapa ya forma parte de la hoja de ruta del producto.',
            ]);
        }

        DB::transaction(function () use ($actor, $articuloId, $etapaId, $tiempoPorUnidad, $direccionIp): void {
            $asignacion = AsignacionDeEtapa::create([
                'articulo_id' => $articuloId,
                'etapa_id' => $etapaId,
                'tiempo_por_unidad' => $tiempoPorUnidad,
            ]);

            $this->auditoria->registrarEvento(
                $actor->id,
                'asignar_etapa_producto',
                null,
                ['articulo_id' => $articuloId, 'etapa_id' => $etapaId, 'tiempo_por_unidad' => $tiempoPorUnidad],
                $direccionIp,
                AsignacionDeEtapa::class,
                $asignacion->id,
            );
        });
    }

    public function desasignar(Usuario $actor, int $articuloId, int $etapaId, ?string $direccionIp = null): void
    {
        $enCurso = OrdenDeProduccion::query()
            ->where('articulo_id', $articuloId)
            ->where('estado', EstadoOrdenProduccion::EnCurso->value)
            ->exists();

        if ($enCurso) {
            throw ValidationException::withMessages([
                'articulo' => 'Existen órdenes de producción en curso con esta hoja de ruta. No se puede desasignar.',
            ]);
        }

        $asignacion = AsignacionDeEtapa::query()
            ->where('articulo_id', $articuloId)
            ->where('etapa_id', $etapaId)
            ->first();

        if ($asignacion === null) {
            throw ValidationException::withMessages([
                'etapa_id' => 'La etapa no está asignada a este producto.',
            ]);
        }

        DB::transaction(function () use ($actor, $asignacion, $articuloId, $etapaId, $direccionIp): void {
            $this->auditoria->registrarEvento(
                $actor->id,
                'desasignar_etapa_producto',
                ['articulo_id' => $articuloId, 'etapa_id' => $etapaId],
                null,
                $direccionIp,
                AsignacionDeEtapa::class,
                $asignacion->id,
            );

            $asignacion->delete();
        });
    }
}
