<?php

namespace App\Modules\Inventario\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Inventario\Models\UnidadDeMedida;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionUnidadesDeMedidaService
{
    public function __construct(private readonly AuditoriaService $auditoria) {}

    /**
     * @return Collection<int, array{id:int, nombre:string, articulos:int}>
     */
    public function listar(): Collection
    {
        return UnidadDeMedida::query()
            ->withCount('articulos')
            ->orderBy('nombre')
            ->get()
            ->map(fn (UnidadDeMedida $unidad): array => [
                'id' => $unidad->id,
                'nombre' => $unidad->nombre,
                'articulos' => (int) $unidad->articulos_count,
            ]);
    }

    public function crear(Usuario $actor, string $nombre, ?string $direccionIp = null): UnidadDeMedida
    {
        $this->asegurarNombreDisponible($nombre);

        return DB::transaction(function () use ($actor, $nombre, $direccionIp): UnidadDeMedida {
            $unidad = UnidadDeMedida::create(['nombre' => $nombre]);

            $this->auditoria->registrarEvento(
                $actor->id,
                'crear_unidad_de_medida',
                null,
                ['nombre' => $nombre],
                $direccionIp,
                UnidadDeMedida::class,
                $unidad->id,
            );

            return $unidad;
        });
    }

    public function actualizar(
        Usuario $actor,
        UnidadDeMedida $unidad,
        string $nombre,
        ?string $direccionIp = null,
    ): UnidadDeMedida {
        $this->asegurarNombreDisponible($nombre, $unidad->id);

        return DB::transaction(function () use ($actor, $unidad, $nombre, $direccionIp): UnidadDeMedida {
            $anterior = ['nombre' => $unidad->nombre];

            $unidad->update(['nombre' => $nombre]);

            $this->auditoria->registrarEvento(
                $actor->id,
                'actualizar_unidad_de_medida',
                $anterior,
                ['nombre' => $nombre],
                $direccionIp,
                UnidadDeMedida::class,
                $unidad->id,
            );

            return $unidad;
        });
    }

    public function eliminar(Usuario $actor, UnidadDeMedida $unidad, ?string $direccionIp = null): void
    {
        if ($unidad->articulos()->exists()) {
            throw ValidationException::withMessages([
                'unidad' => 'La unidad de medida está en uso por artículos y no se puede eliminar.',
            ]);
        }

        DB::transaction(function () use ($actor, $unidad, $direccionIp): void {
            $this->auditoria->registrarEvento(
                $actor->id,
                'eliminar_unidad_de_medida',
                ['nombre' => $unidad->nombre],
                null,
                $direccionIp,
                UnidadDeMedida::class,
                $unidad->id,
            );

            $unidad->delete();
        });
    }

    private function asegurarNombreDisponible(string $nombre, ?int $ignorarId = null): void
    {
        $existe = UnidadDeMedida::query()
            ->whereRaw('lower(nombre) = lower(?)', [$nombre])
            ->when($ignorarId !== null, fn ($query) => $query->whereKeyNot($ignorarId))
            ->exists();

        if ($existe) {
            throw ValidationException::withMessages([
                'nombre' => 'La unidad de medida ya existe.',
            ]);
        }
    }
}
