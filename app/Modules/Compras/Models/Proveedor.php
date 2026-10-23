<?php

namespace App\Modules\Compras\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedor extends Model
{
    /**
     * @var string
     */
    protected $table = 'proveedores';

    /**
     * @var array<int, string>
     */
    protected $fillable = ['razon_social', 'cuit', 'email', 'telefono', 'estado'];

    public function solicitudesDeDisponibilidad(): HasMany
    {
        return $this->hasMany(SolicitudDeDisponibilidad::class);
    }

    public function solicitudesDeCotizacion(): BelongsToMany
    {
        return $this->belongsToMany(SolicitudDeCotizacion::class, 'solicitud_de_cotizacion_proveedor')
            ->withPivot('token');
    }

    public function cotizaciones(): HasMany
    {
        return $this->hasMany(Cotizacion::class);
    }

    public function ordenesDeCompra(): HasMany
    {
        return $this->hasMany(OrdenDeCompra::class);
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
