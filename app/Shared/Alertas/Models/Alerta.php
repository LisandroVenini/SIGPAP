<?php

namespace App\Shared\Alertas\Models;

use Illuminate\Database\Eloquent\Model;

class Alerta extends Model
{
    /**
     * @var string
     */
    protected $table = 'alertas';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'tipo',
        'fecha',
        'descripcion',
        'origen',
        'orden_de_produccion_id',
        'articulo_id',
        'maquinaria_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
        ];
    }
}
