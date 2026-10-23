<?php

namespace App\Modules\Inventario\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoDeStock extends Model
{
    /**
     * @var string
     */
    protected $table = 'movimientos_de_stock';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'articulo_id',
        'orden_de_produccion_id',
        'orden_de_compra_id',
        'tipo',
        'cantidad',
        'fecha',
        'motivo',
        'numero_remito',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
            'fecha' => 'date',
        ];
    }

    public function articulo(): BelongsTo
    {
        return $this->belongsTo(Articulo::class);
    }
}
