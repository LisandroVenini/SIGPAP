<?php

namespace App\Modules\Compras\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesempenoProveedor extends Model
{
    /**
     * @var string
     */
    protected $table = 'desempenos_de_proveedor';

    /**
     * @var array<int, string>
     */
    protected $fillable = ['proveedor_id', 'orden_de_compra_id', 'tiempo_entrega_real', 'fecha_registro'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tiempo_entrega_real' => 'integer',
            'fecha_registro' => 'date',
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
