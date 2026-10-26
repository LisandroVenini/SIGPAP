<?php

namespace App\Modules\Auditoria\Services;

use App\Modules\Auditoria\Models\EventoDeAuditoria;

class AuditoriaService
{
    /**
     * Register an immutable audit event.
     *
     * The actor is received by id so this module never reaches another
     * module's Eloquent model (Rule 5).
     */
    public function registrarEvento(
        int $usuarioId,
        string $accion,
        ?array $estadoAnterior = null,
        ?array $estadoNuevo = null,
        ?string $direccionIp = null,
        ?string $auditableType = null,
        ?int $auditableId = null,
    ): EventoDeAuditoria {
        return EventoDeAuditoria::create([
            'usuario_id' => $usuarioId,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'fecha_hora' => now(),
            'direccion_ip' => $direccionIp,
            'accion' => $accion,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $estadoNuevo,
        ]);
    }
}
