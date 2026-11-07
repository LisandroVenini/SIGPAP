<?php

namespace App\Modules\Produccion\Models;

use Database\Factories\OperarioFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Operario extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected static function newFactory(): OperarioFactory
    {
        return OperarioFactory::new();
    }

    /**
     * @var string
     */
    protected $table = 'operarios';

    /**
     * @var array<int, string>
     */
    protected $fillable = ['nombre', 'apellido', 'especialidad'];

    public function scopeBuscar(Builder $query, string $termino): void
    {
        $termino = addcslashes($termino, '%_\\');

        $query->where(function (Builder $query) use ($termino): void {
            $query->where('nombre', 'like', "%{$termino}%")
                ->orWhere('apellido', 'like', "%{$termino}%")
                ->orWhere('especialidad', 'like', "%{$termino}%");
        });
    }

    public function ordenesDeProduccion(): BelongsToMany
    {
        return $this->belongsToMany(OrdenDeProduccion::class, 'orden_de_produccion_operario');
    }
}
