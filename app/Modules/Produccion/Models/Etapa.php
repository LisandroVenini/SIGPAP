<?php

namespace App\Modules\Produccion\Models;

use Database\Factories\EtapaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Etapa extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected static function newFactory(): EtapaFactory
    {
        return EtapaFactory::new();
    }

    /**
     * @var string
     */
    protected $table = 'etapas';

    /**
     * @var array<int, string>
     */
    protected $fillable = ['nombre', 'descripcion'];

    public function scopeBuscar(Builder $query, string $termino): void
    {
        $termino = addcslashes($termino, '%_\\');

        $query->where(function (Builder $query) use ($termino): void {
            $query->where('nombre', 'like', "%{$termino}%")
                ->orWhere('descripcion', 'like', "%{$termino}%");
        });
    }

    public function scopeConNombre(Builder $query, string $nombre): void
    {
        $query->whereRaw('lower(nombre) = lower(?)', [$nombre]);
    }

    public function asignacionesDeEtapa(): HasMany
    {
        return $this->hasMany(AsignacionDeEtapa::class);
    }

    public function partesDiarios(): HasMany
    {
        return $this->hasMany(ParteDiario::class);
    }
}
