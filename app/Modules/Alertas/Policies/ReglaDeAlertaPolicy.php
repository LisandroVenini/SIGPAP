<?php

namespace App\Modules\Alertas\Policies;

use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use App\Shared\Alertas\Catalogo\CatalogoDeAlertas;
use App\Shared\Alertas\Enums\ModuloDeAlerta;
use App\Shared\Alertas\Models\ReglaDeAlerta;

class ReglaDeAlertaPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return true;
    }

    public function create(Usuario $usuario, ?string $tipo = null): bool
    {
        $definicion = $tipo !== null ? CatalogoDeAlertas::obtener($tipo) : null;

        return $definicion !== null && $this->puedeGestionarModulo($usuario, $definicion->modulo);
    }

    public function update(Usuario $usuario, ReglaDeAlerta $regla): bool
    {
        $modulo = ModuloDeAlerta::tryFrom($regla->modulo);

        if ($modulo === null || ! $this->puedeGestionarModulo($usuario, $modulo)) {
            return false;
        }

        return $this->esAdminOGerente($usuario) || $regla->creada_por === $usuario->id;
    }

    public function delete(Usuario $usuario, ReglaDeAlerta $regla): bool
    {
        return $this->update($usuario, $regla);
    }

    private function puedeGestionarModulo(Usuario $usuario, ModuloDeAlerta $modulo): bool
    {
        return $this->esAdminOGerente($usuario) || $usuario->can($modulo->permiso());
    }

    private function esAdminOGerente(Usuario $usuario): bool
    {
        return $usuario->hasAnyRole([Rol::Administrador->value, Rol::Gerente->value]);
    }
}
