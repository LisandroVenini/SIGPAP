<?php

namespace App\Modules\Produccion\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use App\Modules\Produccion\Models\Operario;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\UsuariosService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionOperariosService
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
        return Operario::query()
            ->when($filtros['buscar'] ?? null, fn ($query, string $buscar) => $query->buscar($buscar))
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Operario $operario) => [
                'id' => $operario->id,
                'nombre' => $operario->nombre,
                'apellido' => $operario->apellido,
                'especialidad' => $operario->especialidad,
            ]);
    }

    /**
     * @param  array{nombre: string, apellido: string, especialidad: string}  $datos
     */
    public function crear(Usuario $actor, array $datos, ?string $direccionIp = null): Operario
    {
        return DB::transaction(function () use ($actor, $datos, $direccionIp): Operario {
            $operario = Operario::create($datos);

            $this->auditoria->registrarEvento(
                $actor->id,
                'crear_operario',
                null,
                ['nombre' => $operario->nombre, 'apellido' => $operario->apellido],
                $direccionIp,
                Operario::class,
                $operario->id,
            );

            return $operario;
        });
    }

    /**
     * @param  array{nombre: string, apellido: string, especialidad: string}  $datos
     */
    public function actualizar(Usuario $actor, Operario $operario, array $datos, ?string $direccionIp = null): Operario
    {
        return DB::transaction(function () use ($actor, $operario, $datos, $direccionIp): Operario {
            $anterior = [
                'nombre' => $operario->nombre,
                'apellido' => $operario->apellido,
                'especialidad' => $operario->especialidad,
            ];

            $operario->update($datos);

            $this->auditoria->registrarEvento(
                $actor->id,
                'actualizar_operario',
                $anterior,
                $datos,
                $direccionIp,
                Operario::class,
                $operario->id,
            );

            return $operario;
        });
    }

    public function eliminar(Usuario $actor, Operario $operario, ?string $direccionIp = null): void
    {
        DB::transaction(function () use ($actor, $operario, $direccionIp): void {
            $enCurso = $operario->ordenesDeProduccion()
                ->where('estado', EstadoOrdenProduccion::EnCurso->value)
                ->exists();

            if ($enCurso) {
                throw ValidationException::withMessages([
                    'operario' => 'El operario está asignado a una orden de producción en curso.',
                ]);
            }

            $this->auditoria->registrarEvento(
                $actor->id,
                'eliminar_operario',
                ['nombre' => $operario->nombre, 'apellido' => $operario->apellido],
                null,
                $direccionIp,
                Operario::class,
                $operario->id,
            );

            $operario->delete();
        });
    }
}
