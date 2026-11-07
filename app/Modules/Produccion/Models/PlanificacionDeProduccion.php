<?php

namespace App\Modules\Produccion\Models;

use Database\Factories\PlanificacionDeProduccionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanificacionDeProduccion extends Model
{
    use HasFactory;

    protected static function newFactory(): PlanificacionDeProduccionFactory
    {
        return PlanificacionDeProduccionFactory::new();
    }

    /**
     * @var string
     */
    protected $table = 'planificaciones_de_produccion';

    /**
     * @var array<int, string>
     */
    protected $fillable = ['nombre', 'fecha_inicio', 'fecha_fin', 'observaciones'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    public function scopeBuscar(Builder $query, string $termino): void
    {
        $termino = addcslashes($termino, '%_\\');

        $query->where(function (Builder $query) use ($termino): void {
            $query->where('nombre', 'like', "%{$termino}%")
                ->orWhere('observaciones', 'like', "%{$termino}%");
        });
    }

    public function ordenesDeProduccion(): HasMany
    {
        return $this->hasMany(OrdenDeProduccion::class);
    }
}
