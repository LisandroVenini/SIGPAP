<?php

namespace App\Modules\Produccion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlDeCalidad extends Model
{
    /**
     * @var string
     */
    protected $table = 'controles_de_calidad';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'orden_de_produccion_id',
        'fecha',
        'cantidad_aprobada',
        'cantidad_rechazada',
        'motivo_rechazo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'cantidad_aprobada' => 'decimal:2',
            'cantidad_rechazada' => 'decimal:2',
        ];
    }

    public function ordenDeProduccion(): BelongsTo
    {
        return $this->belongsTo(OrdenDeProduccion::class);
    }
}
