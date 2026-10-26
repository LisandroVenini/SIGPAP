<?php

namespace App\Modules\Produccion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Maquinaria extends Model
{
    /**
     * @var string
     */
    protected $table = 'maquinarias';

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
