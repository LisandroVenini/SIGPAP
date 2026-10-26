<?php

namespace App\Modules\Compras\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SolicitudDeCotizacion extends Model
{
    /**
     * @var string
     */
    protected $table = 'solicitudes_de_cotizacion';

    /**
     * @var array<int, string>
     */
    protected $fillable = ['fecha', 'fecha_vencimiento', 'estado'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'fecha_vencimiento' => 'date',
        ];
    }

    public function proveedores(): BelongsToMany
    {
        return $this->belongsToMany(Proveedor::class, 'solicitud_de_cotizacion_proveedor')
            ->withPivot('token');
    }

    public function lineas(): HasMany
    {
        return $this->hasMany(LineaSolicitudCotizacion::class);
    }

    public function cotizaciones(): HasMany
    {
        return $this->hasMany(Cotizacion::class);
    }
}
