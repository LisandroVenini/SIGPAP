<?php

namespace App\Modules\Auditoria\Services;

use App\Modules\Auditoria\Queries\EventosDeAuditoriaQuery;
use App\Modules\Usuarios\Services\UsuariosService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ConsultaAuditoriaService
{
    public function __construct(
        private readonly EventosDeAuditoriaQuery $eventos,
        private readonly UsuariosService $usuarios,
    ) {}

    /**
     * List the audit trail together with the filter options.
     *
     * @param  array{usuario_id?: int|string|null, accion?: string|null, direccion_ip?: string|null, fecha_desde?: string|null, fecha_hasta?: string|null}  $filtros
     * @return array{eventos: LengthAwarePaginator, usuarios: array<int, array{id: int, nombre: string}>, acciones: array<int, string>}
     */
    public function listar(array $filtros = []): array
    {
        $eventos = $this->eventos->listar($filtros);

        $usuarios = array_values(array_map(
            fn (array $usuario): array => ['id' => $usuario['id'], 'nombre' => $usuario['nombre']],
            $this->usuarios->resumenesDeUsuarios($this->eventos->usuariosRegistrados()),
        ));

        return [
            'eventos' => $eventos,
            'usuarios' => $usuarios,
            'acciones' => $this->eventos->accionesRegistradas(),
        ];
    }
}
