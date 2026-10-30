<?php

namespace App\Modules\Produccion\Models;

use Database\Factories\OrdenDeProduccionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdenDeProduccion extends Model
{
    use HasFactory;

    protected static function newFactory(): OrdenDeProduccionFactory
    {
        return OrdenDeProduccionFactory::new();
    }

    /**
     * @var string
     */
    protected $table = 'ordenes_de_produccion';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'articulo_id',
        'planificacion_de_produccion_id',
        'estado',
        'cantidad_requerida',
        'fecha_entrega',
        'fecha_inicio',
        'fecha_fin',
        'cantidad_producida',
        'prioridad_actual',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cantidad_requerida' => 'decimal:2',
            'fecha_entrega' => 'date',
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'cantidad_producida' => 'decimal:2',
            'prioridad_actual' => 'integer',
        ];
    }

    /**
     * @param  array<int, int>  $articuloIds
     */
    public function scopeBuscarPorArticulos(Builder $query, array $articuloIds): void
    {
        $query->whereIn('articulo_id', $articuloIds);
    }

    public function scopeConEstado(Builder $query, string $estado): void
    {
        $query->where('estado', $estado);
    }

    public function planificacionDeProduccion(): BelongsTo
    {
        return $this->belongsTo(PlanificacionDeProduccion::class);
    }

    public function registrosDePrioridad(): HasMany
    {
        return $this->hasMany(RegistroPrioridad::class);
    }

    public function partesDiarios(): HasMany
    {
        return $this->hasMany(ParteDiario::class);
    }

    public function controlesDeCalidad(): HasMany
    {
        return $this->hasMany(ControlDeCalidad::class);
    }

    public function operarios(): BelongsToMany
    {
        return $this->belongsToMany(Operario::class, 'orden_de_produccion_operario');
    }

    public function maquinarias(): BelongsToMany
    {
        return $this->belongsToMany(Maquinaria::class, 'orden_de_produccion_maquinaria');
    }
}
