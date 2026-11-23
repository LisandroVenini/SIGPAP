<?php

namespace App\Modules\Produccion\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * National holiday synced from the official open dataset.
 *
 * It is reference data, not configuration: the working calendar reads it to
 * decide whether a date is available for production.
 */
class Feriado extends Model
{
    /**
     * @var string
     */
    protected $table = 'feriados';

    /**
     * @var array<int, string>
     */
    protected $fillable = ['fecha', 'nombre', 'tipo', 'fuente', 'sincronizado_en'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'sincronizado_en' => 'datetime',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeEnRango($query, string $desde, string $hasta): Builder
    {
        return $query->whereBetween('fecha', [$desde, $hasta]);
    }
}
