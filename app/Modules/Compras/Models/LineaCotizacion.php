<?php

namespace App\Modules\Compras\Models;

use Database\Factories\LineaCotizacionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LineaCotizacion extends Model
{
    use HasFactory;

    protected static function newFactory(): LineaCotizacionFactory
    {
        return LineaCotizacionFactory::new();
    }

    /**
     * @var string
     */
    protected $table = 'lineas_de_cotizacion';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'cotizacion_id',
        'articulo_id',
        'precio_unitario',
        'cantidad_ofertada',
        'fecha_prometida',
        'adjudicada',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'precio_unitario' => 'decimal:2',
            'cantidad_ofertada' => 'decimal:2',
            'fecha_prometida' => 'date',
            'adjudicada' => 'boolean',
        ];
    }

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }
}
