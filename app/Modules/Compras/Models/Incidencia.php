<?php

namespace App\Modules\Compras\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Incidencia extends Model
{
    /**
     * @var string
     */
    protected $table = 'incidencias';

    /**
     * @var array<int, string>
     */
    protected $fillable = ['proveedor_id', 'orden_de_compra_id', 'tipo', 'fecha', 'detalle'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function ordenDeCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenDeCompra::class);
    }
}
