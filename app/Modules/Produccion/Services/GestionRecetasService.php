<?php

namespace App\Modules\Produccion\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Inventario\Services\GestionArticulosService;
use App\Modules\Produccion\Models\ComponenteDeReceta;
use App\Modules\Produccion\Models\Receta;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\UsuariosService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionRecetasService
{
    public function __construct(
        private readonly AuditoriaService $auditoria,
        private readonly GestionArticulosService $articulos,
        private readonly UsuariosService $usuarios,
    ) {}

    public function puedeGestionar(Usuario $actor): bool
    {
        return $this->usuarios->autorizar($actor, 'produccion.gestionar');
    }

    /**
     * @return array{componentes: array<int, array{id: int, articulo_id: int, codigo_sku: string, descripcion: string, unidad: string|null, cantidad: float}>, disponibles: array<int, array{id: int, codigo_sku: string, descripcion: string, unidad: string|null}>}
     */
    public function detalle(int $articuloId): array
    {
        $receta = Receta::query()->where('articulo_id', $articuloId)->first();

        if ($receta === null) {
            return [
                'componentes' => [],
                'disponibles' => $this->articulos->insumosDisponibles()->all(),
            ];
        }

        $componentes = $receta->componentesDeReceta()->get();

        $articuloIds = $componentes->pluck('articulo_id')->all();
        $resumenes = $this->articulos->resumenesDeArticulos($articuloIds);

        $componentesFormateados = $componentes
            ->map(fn (ComponenteDeReceta $componente): array => [
                'id' => $componente->id,
                'articulo_id' => $componente->articulo_id,
                'codigo_sku' => $resumenes[$componente->articulo_id]['codigo_sku'] ?? '',
                'descripcion' => $resumenes[$componente->articulo_id]['descripcion'] ?? '',
                'unidad' => $resumenes[$componente->articulo_id]['unidad'] ?? null,
                'cantidad' => (float) $componente->cantidad,
            ])
            ->sortBy('descripcion')
            ->values()
            ->all();

        $disponibles = $this->articulos->insumosDisponibles()
            ->whereNotIn('id', $articuloIds)
            ->values()
            ->all();

        return [
            'componentes' => $componentesFormateados,
            'disponibles' => $disponibles,
        ];
    }

    public function agregarComponente(
        Usuario $actor,
        int $articuloId,
        int $articuloInsumoId,
        float $cantidad,
        ?string $direccionIp = null,
    ): ComponenteDeReceta {
        if ($this->articulos->productoTerminadoFabricado($articuloId) === null) {
            throw ValidationException::withMessages([
                'articulo' => 'Solo los productos terminados de origen Fabricado tienen receta.',
            ]);
        }

        if (! $this->esInsumoActivo($articuloInsumoId)) {
            throw ValidationException::withMessages([
                'articulo_insumo' => 'El insumo debe ser Materia Prima o Insumo Consumible y estar Activo.',
            ]);
        }

        if ($cantidad <= 0) {
            throw ValidationException::withMessages([
                'cantidad' => 'La cantidad debe ser mayor a 0.',
            ]);
        }

        return DB::transaction(function () use ($actor, $articuloId, $articuloInsumoId, $cantidad, $direccionIp): ComponenteDeReceta {
            $receta = Receta::query()->firstOrCreate(['articulo_id' => $articuloId]);

            if ($receta->componentesDeReceta()->where('articulo_id', $articuloInsumoId)->exists()) {
                throw ValidationException::withMessages([
                    'articulo_insumo' => 'Ese insumo ya está en la receta.',
                ]);
            }

            try {
                $componente = $receta->componentesDeReceta()->create([
                    'articulo_id' => $articuloInsumoId,
                    'cantidad' => $cantidad,
                ]);
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages([
                    'articulo_insumo' => 'Ese insumo ya está en la receta.',
                ]);
            }
            $this->auditoria->registrarEvento(
                $actor->id,
                'agregar_componente_receta',
                null,
                [
                    'articulo_id' => $articuloId,
                    'articulo_insumo_id' => $articuloInsumoId,
                    'cantidad' => $cantidad,
                ],
                $direccionIp,
                Receta::class,
                $receta->id,
            );

            return $componente;
        });
    }

    public function quitarComponente(
        Usuario $actor,
        int $articuloId,
        ComponenteDeReceta $componente,
        ?string $direccionIp = null,
    ): void {
        if ($componente->receta?->articulo_id !== $articuloId) {
            throw (new ModelNotFoundException)->setModel(ComponenteDeReceta::class, [$componente->id]);
        }

        DB::transaction(function () use ($actor, $componente, $direccionIp): void {
            $this->auditoria->registrarEvento(
                $actor->id,
                'quitar_componente_receta',
                [
                    'articulo_id' => $componente->receta->articulo_id,
                    'articulo_insumo_id' => $componente->articulo_id,
                    'cantidad' => (float) $componente->cantidad,
                ],
                null,
                $direccionIp,
                Receta::class,
                $componente->receta_id,
            );

            $componente->delete();
        });
    }

    private function esInsumoActivo(int $articuloId): bool
    {
        return $this->articulos->insumosDisponibles()->contains('id', $articuloId);
    }
}
