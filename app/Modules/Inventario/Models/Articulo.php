<?php

namespace App\Modules\Inventario\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Articulo extends Model
{
    /**
     * @var string
     */
    protected $table = 'articulos';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'codigo_sku',
        'descripcion',
        'tipo',
        'origen',
        'stock_fisico',
        'stock_minimo',
        'stock_maximo',
        'fecha_vencimiento',
        'estado',
        'unidad_de_medida_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stock_fisico' => 'decimal:2',
            'stock_minimo' => 'decimal:2',
            'stock_maximo' => 'decimal:2',
            'fecha_vencimiento' => 'date',
        ];
    }

    public function unidadDeMedida(): BelongsTo
    {
        return $this->belongsTo(UnidadDeMedida::class);
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }

    public function movimientosDeStock(): HasMany
    {
        return $this->hasMany(MovimientoDeStock::class);
    }
}
