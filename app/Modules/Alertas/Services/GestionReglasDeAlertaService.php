<?php

namespace App\Modules\Alertas\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Usuarios\Models\Usuario;
use App\Shared\Alertas\Catalogo\CatalogoDeAlertas;
use App\Shared\Alertas\Catalogo\TipoDeAlerta;
use App\Shared\Alertas\Models\ReglaDeAlerta;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionReglasDeAlertaService
{
    public function __construct(private readonly AuditoriaService $auditoria) {}

    /**
     * @param  array{buscar?: string|null, modulo?: string|null, activa?: bool|null}  $filtros
     */
    public function listar(array $filtros = []): LengthAwarePaginator
    {
        return ReglaDeAlerta::query()
            ->with(['roles', 'creador'])
            ->when($filtros['buscar'] ?? null, fn ($query, string $buscar) => $query->where('nombre', 'ilike', "%{$buscar}%"))
            ->when($filtros['modulo'] ?? null, fn ($query, string $modulo) => $query->where('modulo', $modulo))
            ->when(array_key_exists('activa', $filtros) && $filtros['activa'] !== null, fn ($query) => $query->where('activa', (bool) $filtros['activa']))
            ->orderBy('modulo')
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @param  array{nombre: string, descripcion?: string|null, tipo: string, severidad: string, mensaje: string, activa?: bool, roles: array<int, int>, condiciones?: array<string, mixed>}  $datos
     */
    public function crear(Usuario $actor, array $datos, ?string $direccionIp = null): ReglaDeAlerta
    {
        $tipo = CatalogoDeAlertas::obtener($datos['tipo']);

        if ($tipo === null) {
            throw ValidationException::withMessages(['tipo' => 'El tipo de alerta no es válido.']);
        }

        return DB::transaction(function () use ($actor, $datos, $tipo, $direccionIp): ReglaDeAlerta {
            $regla = ReglaDeAlerta::create([
                'nombre' => $datos['nombre'],
                'descripcion' => $datos['descripcion'] ?? null,
                'tipo' => $tipo->clave,
                'modulo' => $tipo->modulo->value,
                'severidad' => $datos['severidad'],
                'origen_tipo' => $tipo->origen->value,
                'condiciones' => $this->normalizarCondiciones($tipo, $datos['condiciones'] ?? []),
                'mensaje' => $datos['mensaje'],
                'activa' => $datos['activa'] ?? true,
                'creada_por' => $actor->id,
            ]);

            $regla->roles()->sync($datos['roles']);

            $this->auditoria->registrarEvento(
                $actor->id,
                'crear_regla_alerta',
                null,
                $this->resumen($regla),
                $direccionIp,
                ReglaDeAlerta::class,
                $regla->id,
            );

            return $regla->load('roles');
        });
    }

    /**
     * @param  array{nombre: string, descripcion?: string|null, severidad: string, mensaje: string, activa?: bool, roles: array<int, int>, condiciones?: array<string, mixed>}  $datos
     */
    public function actualizar(Usuario $actor, ReglaDeAlerta $regla, array $datos, ?string $direccionIp = null): ReglaDeAlerta
    {
        $tipo = CatalogoDeAlertas::obtener($regla->tipo);

        if ($tipo === null) {
            throw ValidationException::withMessages(['tipo' => 'El tipo de alerta no es válido.']);
        }

        return DB::transaction(function () use ($actor, $regla, $datos, $tipo, $direccionIp): ReglaDeAlerta {
            $anterior = $this->resumen($regla);

            $regla->update([
                'nombre' => $datos['nombre'],
                'descripcion' => $datos['descripcion'] ?? null,
                'severidad' => $datos['severidad'],
                'condiciones' => $this->normalizarCondiciones($tipo, $datos['condiciones'] ?? []),
                'mensaje' => $datos['mensaje'],
                'activa' => $datos['activa'] ?? $regla->activa,
            ]);

            $regla->roles()->sync($datos['roles']);

            $this->auditoria->registrarEvento(
                $actor->id,
                'actualizar_regla_alerta',
                $anterior,
                $this->resumen($regla),
                $direccionIp,
                ReglaDeAlerta::class,
                $regla->id,
            );

            return $regla->load('roles');
        });
    }

    public function alternarActiva(Usuario $actor, ReglaDeAlerta $regla, ?string $direccionIp = null): ReglaDeAlerta
    {
        return DB::transaction(function () use ($actor, $regla, $direccionIp): ReglaDeAlerta {
            $anterior = ['activa' => $regla->activa];

            $regla->update(['activa' => ! $regla->activa]);

            $this->auditoria->registrarEvento(
                $actor->id,
                'cambiar_estado_regla_alerta',
                $anterior,
                ['activa' => $regla->activa],
                $direccionIp,
                ReglaDeAlerta::class,
                $regla->id,
            );

            return $regla;
        });
    }

    public function eliminar(Usuario $actor, ReglaDeAlerta $regla, ?string $direccionIp = null): void
    {
        DB::transaction(function () use ($actor, $regla, $direccionIp): void {
            $anterior = $this->resumen($regla);

            $this->auditoria->registrarEvento(
                $actor->id,
                'eliminar_regla_alerta',
                $anterior,
                null,
                $direccionIp,
                ReglaDeAlerta::class,
                $regla->id,
            );

            $regla->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $condiciones
     * @return array<string, float|int>
     */
    private function normalizarCondiciones(TipoDeAlerta $tipo, array $condiciones): array
    {
        $normalizadas = [];

        foreach ($tipo->condiciones as $condicion) {
            $valor = $condiciones[$condicion['clave']] ?? $condicion['por_defecto'];
            $normalizadas[$condicion['clave']] = $condicion['tipo'] === 'integer'
                ? (int) $valor
                : (float) $valor;
        }

        return $normalizadas;
    }

    /**
     * @return array<string, mixed>
     */
    private function resumen(ReglaDeAlerta $regla): array
    {
        return [
            'nombre' => $regla->nombre,
            'tipo' => $regla->tipo,
            'modulo' => $regla->modulo,
            'severidad' => $regla->severidad,
            'condiciones' => $regla->condiciones,
            'activa' => $regla->activa,
            'roles' => $regla->roles()->pluck('roles.name')->all(),
        ];
    }
}
