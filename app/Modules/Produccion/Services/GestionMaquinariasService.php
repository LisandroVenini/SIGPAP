<?php

namespace App\Modules\Produccion\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Produccion\Enums\EstadoMaquinaria;
use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use App\Modules\Produccion\Models\Maquinaria;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\UsuariosService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionMaquinariasService
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
        return Maquinaria::query()
            ->withCount('planesDeMantenimiento')
            ->withCount('intervencionesDeMantenimiento')
            ->when($filtros['buscar'] ?? null, fn ($query, string $buscar) => $query->buscar($buscar))
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Maquinaria $maquinaria) => [
                'id' => $maquinaria->id,
                'nombre' => $maquinaria->nombre,
                'tipo' => $maquinaria->tipo,
                'estado_operativo' => $maquinaria->estado_operativo,
                'desgaste_acumulado' => $maquinaria->desgaste_acumulado,
                'planes' => $maquinaria->planes_de_mantenimiento_count,
                'intervenciones' => $maquinaria->intervenciones_de_mantenimiento_count,
            ]);
    }

    /**
     * @param  array{nombre: string, tipo: string}  $datos
     */
    public function crear(Usuario $actor, array $datos, ?string $direccionIp = null): Maquinaria
    {
        return DB::transaction(function () use ($actor, $datos, $direccionIp): Maquinaria {
            $maquinaria = Maquinaria::create([
                ...$datos,
                'estado_operativo' => EstadoMaquinaria::Disponible->value,
                'desgaste_acumulado' => 0,
            ]);

            $this->auditoria->registrarEvento(
                $actor->id,
                'crear_maquinaria',
                null,
                ['nombre' => $maquinaria->nombre, 'tipo' => $maquinaria->tipo],
                $direccionIp,
                Maquinaria::class,
                $maquinaria->id,
            );

            return $maquinaria;
        });
    }

    /**
     * @param  array{nombre: string, tipo: string}  $datos
     */
    public function actualizar(Usuario $actor, Maquinaria $maquinaria, array $datos, ?string $direccionIp = null): Maquinaria
    {
        return DB::transaction(function () use ($actor, $maquinaria, $datos, $direccionIp): Maquinaria {
            $anterior = [
                'nombre' => $maquinaria->nombre,
                'tipo' => $maquinaria->tipo,
            ];

            $maquinaria->update([
                'nombre' => $datos['nombre'],
                'tipo' => $datos['tipo'],
            ]);

            $this->auditoria->registrarEvento(
                $actor->id,
                'actualizar_maquinaria',
                $anterior,
                $datos,
                $direccionIp,
                Maquinaria::class,
                $maquinaria->id,
            );

            return $maquinaria;
        });
    }

    public function eliminar(Usuario $actor, Maquinaria $maquinaria, ?string $direccionIp = null): void
    {
        DB::transaction(function () use ($actor, $maquinaria, $direccionIp): void {
            $enCurso = $maquinaria->ordenesDeProduccion()
                ->where('estado', EstadoOrdenProduccion::EnCurso->value)
                ->exists();

            if ($enCurso) {
                throw ValidationException::withMessages([
                    'maquinaria' => 'La maquinaria está asignada a una orden de producción en curso.',
                ]);
            }

            if ($maquinaria->planesDeMantenimiento()->exists()) {
                throw ValidationException::withMessages([
                    'maquinaria' => 'La maquinaria tiene planes de mantenimiento registrados.',
                ]);
            }

            if ($maquinaria->intervencionesDeMantenimiento()->exists()) {
                throw ValidationException::withMessages([
                    'maquinaria' => 'La maquinaria tiene intervenciones de mantenimiento registradas.',
                ]);
            }

            $this->auditoria->registrarEvento(
                $actor->id,
                'eliminar_maquinaria',
                ['nombre' => $maquinaria->nombre, 'tipo' => $maquinaria->tipo],
                null,
                $direccionIp,
                Maquinaria::class,
                $maquinaria->id,
            );

            $maquinaria->delete();
        });
    }
}
