<?php

namespace App\Modules\Produccion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanificacionDeProduccion extends Model
{
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

    public function ordenesDeProduccion(): HasMany
    {
        return $this->hasMany(OrdenDeProduccion::class);
    }
}
