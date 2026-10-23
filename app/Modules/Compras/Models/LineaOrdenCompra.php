<?php

namespace App\Modules\Compras\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LineaOrdenCompra extends Model
{
    /**
     * @var string
     */
    protected $table = 'lineas_de_orden_de_compra';

    /**
     * @var array<int, string>
     */
    protected $fillable = ['orden_de_compra_id', 'articulo_id', 'cantidad', 'precio_pactado'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
            'precio_pactado' => 'decimal:2',
        ];
    }

    public function ordenDeCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenDeCompra::class);
    }
}
