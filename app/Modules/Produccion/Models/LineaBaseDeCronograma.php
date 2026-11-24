<?php

namespace App\Modules\Produccion\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Planned start and end of one stage of an order, captured once when the order
 * is assigned. It is the baseline the Gantt compares against the projection.
 */
class LineaBaseDeCronograma extends Model
{
    /**
     * @var string
     */
    protected $table = 'lineas_base_de_cronograma';

    /**
     * @var array<int, string>
     */
    protected $fillable = ['orden_de_produccion_id', 'etapa_id', 'inicio_plan', 'fin_plan'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'inicio_plan' => 'datetime',
            'fin_plan' => 'datetime',
        ];
    }
}
