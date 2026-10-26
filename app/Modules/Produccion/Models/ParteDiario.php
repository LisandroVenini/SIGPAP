<?php

namespace App\Modules\Produccion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParteDiario extends Model
{
    /**
     * @var string
     */
    protected $table = 'partes_diarios';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'orden_de_produccion_id',
        'etapa_id',
        'maquinaria_id',
        'fecha',
        'cantidad_conformada',
        'observaciones',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'cantidad_conformada' => 'decimal:2',
        ];
    }

    public function ordenDeProduccion(): BelongsTo
    {
        return $this->belongsTo(OrdenDeProduccion::class);
    }

    public function etapa(): BelongsTo
    {
        return $this->belongsTo(Etapa::class);
    }

    public function maquinaria(): BelongsTo
    {
        return $this->belongsTo(Maquinaria::class);
    }
}
