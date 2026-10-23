<?php

namespace App\Modules\Compras\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cotizacion extends Model
{
    /**
     * @var string
     */
    protected $table = 'cotizaciones';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'solicitud_de_cotizacion_id',
        'solicitud_de_disponibilidad_id',
        'proveedor_id',
        'fecha',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    public function solicitudDeCotizacion(): BelongsTo
    {
        return $this->belongsTo(SolicitudDeCotizacion::class);
    }

    public function solicitudDeDisponibilidad(): BelongsTo
    {
        return $this->belongsTo(SolicitudDeDisponibilidad::class);
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function lineas(): HasMany
    {
        return $this->hasMany(LineaCotizacion::class);
    }

    public function ordenesDeCompra(): HasMany
    {
        return $this->hasMany(OrdenDeCompra::class);
    }
}
