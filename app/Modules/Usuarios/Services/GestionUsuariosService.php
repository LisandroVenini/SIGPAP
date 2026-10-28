<?php

namespace App\Modules\Usuarios\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Usuarios\Enums\EstadoUsuario;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Queries\UsuariosQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionUsuariosService
{
    public function __construct(
        private readonly AuditoriaService $auditoria,
        private readonly UsuariosQuery $query,
    ) {}

    public function puedeGestionar(Usuario $actor): bool
    {
        return $actor->hasRole(Rol::Administrador->value);
    }

    /**
     * @param  array{buscar?: string|null, rol?: string|null, estado?: string|null}  $filtros
     */
    public function listar(array $filtros = []): LengthAwarePaginator
    {
        return $this->query->listar($filtros);
    }

    /**
     * @return array{total: int, activos: int, inhabilitados: int}
     */
    public function resumen(): array
    {
        return $this->query->resumen();
    }

    /**
     * @param  array{nombre: string, email: string, password: string, rol: string}  $datos
     */
    public function crear(Usuario $actor, array $datos, ?string $direccionIp = null): Usuario
    {
        return DB::transaction(function () use ($actor, $datos, $direccionIp): Usuario {
            $usuario = Usuario::create([
                'nombre' => $datos['nombre'],
                'email' => $datos['email'],
                'clave_acceso' => $datos['password'],
            ]);

            $usuario->assignRole($datos['rol']);

            $this->auditoria->registrarEvento(
                $actor->id,
                'crear_usuario',
                null,
                ['email' => $usuario->email, 'rol' => $datos['rol']],
                $direccionIp,
                Usuario::class,
                $usuario->id,
            );

            return $usuario;
        });
    }

    /**
     * @param  array{nombre: string, email: string, password?: string|null, rol: string, estado: string}  $datos
     */
    public function actualizar(Usuario $actor, Usuario $usuario, array $datos, ?string $direccionIp = null): Usuario
    {
        $this->asegurarOperacionPermitida($actor, $usuario, $datos['estado'], $datos['rol']);

        return DB::transaction(function () use ($actor, $usuario, $datos, $direccionIp): Usuario {
            $anterior = [
                'nombre' => $usuario->nombre,
                'email' => $usuario->email,
                'rol' => $usuario->getRoleNames()->first(),
                'estado' => $usuario->estado,
                'password_reset' => false,
            ];

            $passwordReset = ! empty($datos['password']);

            $usuario->nombre = $datos['nombre'];
            $usuario->email = $datos['email'];
            $usuario->estado = $datos['estado'];

            if ($passwordReset) {
                $usuario->clave_acceso = $datos['password'];
            }

            $usuario->save();
            $usuario->syncRoles([$datos['rol']]);

            $this->auditoria->registrarEvento(
                $actor->id,
                'actualizar_usuario',
                $anterior,
                [
                    'nombre' => $usuario->nombre,
                    'email' => $usuario->email,
                    'rol' => $datos['rol'],
                    'estado' => $usuario->estado,
                    'password_reset' => $passwordReset,
                ],
                $direccionIp,
                Usuario::class,
                $usuario->id,
            );

            return $usuario;
        });
    }

    public function alternarEstado(Usuario $actor, Usuario $usuario, ?string $direccionIp = null): Usuario
    {
        $nuevoEstado = $usuario->estado === EstadoUsuario::Inhabilitado->value
            ? EstadoUsuario::Activo->value
            : EstadoUsuario::Inhabilitado->value;

        $this->asegurarOperacionPermitida($actor, $usuario, $nuevoEstado);

        return DB::transaction(function () use ($actor, $usuario, $nuevoEstado, $direccionIp): Usuario {
            $anterior = ['estado' => $usuario->estado];

            $usuario->estado = $nuevoEstado;
            $usuario->save();

            $this->auditoria->registrarEvento(
                $actor->id,
                'cambiar_estado_usuario',
                $anterior,
                ['estado' => $usuario->estado],
                $direccionIp,
                Usuario::class,
                $usuario->id,
            );

            return $usuario;
        });
    }

    /**
     * An Administrador cannot leave the system without access to user management.
     */
    private function asegurarOperacionPermitida(
        Usuario $actor,
        Usuario $usuario,
        string $nuevoEstado,
        ?string $nuevoRol = null,
    ): void {
        if (! $actor->is($usuario)) {
            return;
        }

        if ($nuevoEstado === EstadoUsuario::Inhabilitado->value) {
            throw ValidationException::withMessages([
                'estado' => 'No podés inhabilitar tu propia cuenta.',
            ]);
        }

        if ($nuevoRol !== null && $nuevoRol !== Rol::Administrador->value) {
            throw ValidationException::withMessages([
                'rol' => 'No podés quitarte el rol de Administrador.',
            ]);
        }
    }
}
