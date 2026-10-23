<?php

namespace App\Modules\Produccion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanDeMantenimiento extends Model
{
    /**
     * @var string
     */
    protected $table = 'planes_de_mantenimiento';

    /**
     * @var array<int, string>
     */
    protected $fillable = ['maquinaria_id', 'umbral_uso', 'descripcion'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'umbral_uso' => 'decimal:2',
        ];
    }

    public function maquinaria(): BelongsTo
    {
        return $this->belongsTo(Maquinaria::class);
    }
}
