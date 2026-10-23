<?php

namespace App\Modules\Predictivo\Models;

use Illuminate\Database\Eloquent\Model;

class TasaDeRendimiento extends Model
{
    /**
     * @var string
     */
    protected $table = 'tasas_de_rendimiento';

    /**
     * @var array<int, string>
     */
    protected $fillable = ['etapa_id', 'valor', 'desviacion', 'fecha_calculo'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'valor' => 'decimal:4',
            'desviacion' => 'decimal:4',
            'fecha_calculo' => 'date',
        ];
    }
}
