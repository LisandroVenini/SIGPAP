<?php

namespace App\Modules\Produccion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Etapa extends Model
{
    /**
     * @var string
     */
    protected $table = 'etapas';

    /**
     * @var array<int, string>
     */
    protected $fillable = ['nombre', 'descripcion'];

    public function asignacionesDeEtapa(): HasMany
    {
        return $this->hasMany(AsignacionDeEtapa::class);
    }

    public function partesDiarios(): HasMany
    {
        return $this->hasMany(ParteDiario::class);
    }
}
