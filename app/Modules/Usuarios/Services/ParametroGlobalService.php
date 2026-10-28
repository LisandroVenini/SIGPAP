<?php

namespace App\Modules\Usuarios\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\ParametroGlobal;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ParametroGlobalService
{
    public function __construct(private readonly AuditoriaService $auditoria) {}

    /**
     * List every global parameter grouped by category.
     *
     * @return Collection<string, Collection<int, ParametroGlobal>>
     */
    public function listar(): Collection
    {
        return ParametroGlobal::query()
            ->orderBy('categoria')
            ->orderBy('nombre')
            ->get()
            ->groupBy('categoria');
    }

    /**
     * Read a global parameter value. Transversal read access.
     */
    public function parametro(string $clave): ?string
    {
        return ParametroGlobal::where('nombre', $clave)->value('valor');
    }

    /**
     * Whether the actor may view the configuration screen.
     */
    public function puedeVer(Usuario $actor): bool
    {
        return $actor->hasAnyRole([Rol::Administrador->value, Rol::Gerente->value]);
    }

    /**
     * Whether the actor may edit global parameters.
     */
    public function puedeEditar(Usuario $actor): bool
    {
        return $actor->hasRole(Rol::Administrador->value);
    }

    /**
     * Persist several global parameters atomically and audit every change.
     *
     * @param  array<string, string>  $parametros
     */
    public function actualizar(Usuario $actor, array $parametros, ?string $direccionIp = null): void
    {
        if (! $this->puedeEditar($actor)) {
            throw new AuthorizationException('Solo un Administrador puede definir parámetros globales.');
        }

        DB::transaction(function () use ($actor, $parametros, $direccionIp): void {
            foreach ($parametros as $nombre => $valor) {
                $parametro = ParametroGlobal::where('nombre', $nombre)->firstOrFail();
                $valorAnterior = $parametro->valor;

                $parametro->update(['valor' => $valor]);

                $this->auditoria->registrarEvento(
                    $actor->id,
                    'actualizar_parametro',
                    ['valor' => $valorAnterior],
                    ['valor' => $valor],
                    $direccionIp,
                    ParametroGlobal::class,
                    $parametro->id,
                );
            }
        });
    }

    /**
     * Create or update a global parameter. Only an Administrador may write.
     */
    public function definirParametro(
        Usuario $actor,
        string $clave,
        string $valor,
        string $categoria = 'General',
    ): ParametroGlobal {
        if (! $this->puedeEditar($actor)) {
            throw new AuthorizationException('Solo un Administrador puede definir parámetros globales.');
        }

        return ParametroGlobal::updateOrCreate(
            ['nombre' => $clave],
            ['valor' => $valor, 'categoria' => $categoria],
        );
    }
}
