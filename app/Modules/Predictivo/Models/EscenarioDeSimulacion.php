<?php

namespace App\Modules\Predictivo\Models;

use Illuminate\Database\Eloquent\Model;

class EscenarioDeSimulacion extends Model
{
    /**
     * @var string
     */
    protected $table = 'escenarios_de_simulacion';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'articulo_id',
        'cantidad_ficticia',
        'fecha_limite',
        'prioridad',
        'impacto_proyectado',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cantidad_ficticia' => 'decimal:2',
            'fecha_limite' => 'date',
            'prioridad' => 'integer',
            'impacto_proyectado' => 'decimal:2',
        ];
    }
}
