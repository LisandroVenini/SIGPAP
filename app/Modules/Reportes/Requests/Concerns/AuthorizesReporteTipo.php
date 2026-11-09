<?php

namespace App\Modules\Reportes\Requests\Concerns;

use App\Modules\Reportes\Services\GestionReportesService;

trait AuthorizesReporteTipo
{
    /**
     * Authorize the requested report type by the permission of its area.
     *
     * An unknown type is left for the validation rules so the actor receives a
     * validation error instead of a misleading forbidden response.
     */
    protected function autorizarTipoDeReporte(): bool
    {
        $usuario = $this->user();

        if ($usuario === null) {
            return false;
        }

        $tipo = $this->input('tipo');

        if (! is_string($tipo) || $tipo === '') {
            foreach (GestionReportesService::TIPOS as $configuracion) {
                if ($usuario->can($configuracion['permiso'])) {
                    return true;
                }
            }

            return false;
        }

        $permiso = GestionReportesService::permisoDe($tipo);

        if ($permiso === null) {
            return true;
        }

        return $usuario->can($permiso);
    }
}
