<?php

namespace App\Modules\Produccion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroPrioridad extends Model
{
    /**
     * @var string
     */
    protected $table = 'registros_de_prioridad';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'orden_de_produccion_id',
        'usuario_id',
        'prioridad_anterior',
        'prioridad_nueva',
        'fecha_cambio',
        'motivo',
        'detalle',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_cambio' => 'datetime',
            'prioridad_anterior' => 'integer',
            'prioridad_nueva' => 'integer',
        ];
    }

    public function ordenDeProduccion(): BelongsTo
    {
        return $this->belongsTo(OrdenDeProduccion::class);
    }
}
