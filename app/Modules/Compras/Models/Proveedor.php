<?php

namespace App\Modules\Compras\Models;

use Database\Factories\ProveedorFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedor extends Model
{
    use HasFactory;

    protected static function newFactory(): ProveedorFactory
    {
        return ProveedorFactory::new();
    }

    /**
     * @var string
     */
    protected $table = 'proveedores';

    /**
     * @var array<int, string>
     */
    protected $fillable = ['razon_social', 'cuit', 'email', 'telefono', 'estado'];

    public function scopeBuscar(Builder $query, string $termino): void
    {
        $termino = addcslashes($termino, '%_\\');

        $query->where(function (Builder $query) use ($termino): void {
            $query->where('razon_social', 'ilike', "%{$termino}%")
                ->orWhere('cuit', 'ilike', "%{$termino}%")
                ->orWhere('email', 'ilike', "%{$termino}%");
        });
    }

    public function scopeActivos(Builder $query): void
    {
        $query->where('estado', 'Activo')->orderBy('razon_social');
    }

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
