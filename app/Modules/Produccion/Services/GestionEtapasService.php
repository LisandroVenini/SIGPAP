<?php

namespace App\Modules\Produccion\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Produccion\Models\Etapa;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\UsuariosService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionEtapasService
{
    public function __construct(
        private readonly AuditoriaService $auditoria,
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
        return Etapa::query()
            ->withCount('asignacionesDeEtapa')
            ->when($filtros['buscar'] ?? null, fn ($query, string $buscar) => $query->buscar($buscar))
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Etapa $etapa) => [
                'id' => $etapa->id,
                'nombre' => $etapa->nombre,
                'descripcion' => $etapa->descripcion,
                'asignaciones' => $etapa->asignaciones_de_etapa_count,
            ]);
    }

    /**
     * @param  array{nombre: string, descripcion?: string|null}  $datos
     */
    public function crear(Usuario $actor, array $datos, ?string $direccionIp = null): Etapa
    {
        return DB::transaction(function () use ($actor, $datos, $direccionIp): Etapa {
            try {
                $etapa = Etapa::create($datos);
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages([
                    'nombre' => 'Ya existe una etapa con ese nombre.',
                ]);
            }

            $this->auditoria->registrarEvento(
                $actor->id,
                'crear_etapa',
                null,
                ['nombre' => $etapa->nombre],
                $direccionIp,
                Etapa::class,
                $etapa->id,
            );

            return $etapa;
        });
    }

    /**
     * @param  array{nombre: string, descripcion?: string|null}  $datos
     */
    public function actualizar(Usuario $actor, Etapa $etapa, array $datos, ?string $direccionIp = null): Etapa
    {
        return DB::transaction(function () use ($actor, $etapa, $datos, $direccionIp): Etapa {
            $anterior = ['nombre' => $etapa->nombre, 'descripcion' => $etapa->descripcion];

            try {
                $etapa->update($datos);
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages([
                    'nombre' => 'Ya existe una etapa con ese nombre.',
                ]);
            }

            $this->auditoria->registrarEvento(
                $actor->id,
                'actualizar_etapa',
                $anterior,
                $datos,
                $direccionIp,
                Etapa::class,
                $etapa->id,
            );

            return $etapa;
        });
    }

    public function eliminar(Usuario $actor, Etapa $etapa, ?string $direccionIp = null): void
    {
        DB::transaction(function () use ($actor, $etapa, $direccionIp): void {
            if ($etapa->asignacionesDeEtapa()->exists()) {
                throw ValidationException::withMessages([
                    'etapa' => 'La etapa está asignada a uno o más productos. Desasignala antes de eliminarla.',
                ]);
            }

            $this->auditoria->registrarEvento(
                $actor->id,
                'eliminar_etapa',
                ['nombre' => $etapa->nombre],
                null,
                $direccionIp,
                Etapa::class,
                $etapa->id,
            );

            $etapa->delete();
        });
    }
}
