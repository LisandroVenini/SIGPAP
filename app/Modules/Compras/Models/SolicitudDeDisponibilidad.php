<?php

namespace App\Modules\Compras\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SolicitudDeDisponibilidad extends Model
{
    /**
     * @var string
     */
    protected $table = 'solicitudes_de_disponibilidad';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'proveedor_id',
        'articulo_id',
        'fecha',
        'cantidad_solicitada',
        'estado',
        'token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'cantidad_solicitada' => 'decimal:2',
        ];
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function cotizaciones(): HasMany
    {
        return $this->hasMany(Cotizacion::class);
    }

    public function ordenesDeCompra(): HasMany
    {
        return $this->hasMany(OrdenDeCompra::class);
    }
}
