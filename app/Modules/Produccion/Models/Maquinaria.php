<?php

namespace App\Modules\Produccion\Models;

use App\Modules\Produccion\Enums\EstadoMaquinaria;
use Database\Factories\MaquinariaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Maquinaria extends Model
{
    use HasFactory;

    protected static function newFactory(): MaquinariaFactory
    {
        return MaquinariaFactory::new();
    }

    /**
     * @var string
     */
    protected $table = 'maquinarias';

    public function scopeDisponible(Builder $query): void
    {
        $query->where('estado_operativo', EstadoMaquinaria::Disponible->value);
    }

    public function scopeBuscar(Builder $query, string $termino): void
    {
        $termino = addcslashes($termino, '%_\\');

        $query->where(function (Builder $query) use ($termino): void {
            $query->where('nombre', 'like', "%{$termino}%")
                ->orWhere('tipo', 'like', "%{$termino}%");
        });
    }

    /**
     * @var array<int, string>
     */
    protected $fillable = ['nombre', 'tipo', 'estado_operativo', 'desgaste_acumulado'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'desgaste_acumulado' => 'decimal:2',
        ];
    }

    public function ordenesDeProduccion(): BelongsToMany
    {
        return $this->belongsToMany(OrdenDeProduccion::class, 'orden_de_produccion_maquinaria');
    }

    public function planesDeMantenimiento(): HasMany
    {
        return $this->hasMany(PlanDeMantenimiento::class);
    }

    public function intervencionesDeMantenimiento(): HasMany
    {
        return $this->hasMany(IntervencionDeMantenimiento::class);
    }

    public function partesDiarios(): HasMany
    {
        return $this->hasMany(ParteDiario::class);
    }
}
