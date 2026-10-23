<?php

namespace App\Modules\Compras\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LineaSolicitudCotizacion extends Model
{
    /**
     * @var string
     */
    protected $table = 'lineas_de_solicitud_de_cotizacion';

    /**
     * @var array<int, string>
     */
    protected $fillable = ['solicitud_de_cotizacion_id', 'articulo_id', 'cantidad_solicitada'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cantidad_solicitada' => 'decimal:2',
        ];
    }

    public function solicitudDeCotizacion(): BelongsTo
    {
        return $this->belongsTo(SolicitudDeCotizacion::class);
    }
}
