<?php

namespace App\Modules\Compras\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdenDeCompra extends Model
{
    /**
     * @var string
     */
    protected $table = 'ordenes_de_compra';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'numero_oc',
        'proveedor_id',
        'cotizacion_id',
        'solicitud_de_disponibilidad_id',
        'estado',
        'entrega_parcial',
        'fecha_emision',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entrega_parcial' => 'boolean',
            'fecha_emision' => 'date',
        ];
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }

    public function solicitudDeDisponibilidad(): BelongsTo
    {
        return $this->belongsTo(SolicitudDeDisponibilidad::class);
    }

    public function lineas(): HasMany
    {
        return $this->hasMany(LineaOrdenCompra::class);
    }

    public function incidencias(): HasMany
    {
        return $this->hasMany(Incidencia::class);
    }

    public function desempenos(): HasMany
    {
        return $this->hasMany(DesempenoProveedor::class);
    }
}
