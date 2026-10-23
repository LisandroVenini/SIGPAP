<?php

namespace App\Modules\Produccion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsignacionDeEtapa extends Model
{
    /**
     * @var string
     */
    protected $table = 'asignaciones_de_etapa';

    /**
     * @var array<int, string>
     */
    protected $fillable = ['articulo_id', 'etapa_id', 'tiempo_por_unidad'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tiempo_por_unidad' => 'decimal:2',
        ];
    }

    public function etapa(): BelongsTo
    {
        return $this->belongsTo(Etapa::class);
    }
}
