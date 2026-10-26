<?php

namespace App\Modules\Auditoria\Models;

use Illuminate\Database\Eloquent\Model;

class EventoDeAuditoria extends Model
{
    /**
     * @var string
     */
    protected $table = 'eventos_de_auditoria';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'usuario_id',
        'auditable_type',
        'auditable_id',
        'fecha_hora',
        'direccion_ip',
        'accion',
        'estado_anterior',
        'estado_nuevo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
            'estado_anterior' => 'array',
            'estado_nuevo' => 'array',
        ];
    }
}
