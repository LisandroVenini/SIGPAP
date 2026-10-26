<?php

namespace App\Modules\Produccion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Operario extends Model
{
    /**
     * @var string
     */
    protected $table = 'operarios';

    /**
     * @var array<int, string>
     */
    protected $fillable = ['nombre', 'apellido', 'especialidad'];

    public function ordenesDeProduccion(): BelongsToMany
    {
        return $this->belongsToMany(OrdenDeProduccion::class, 'orden_de_produccion_operario');
    }
}
