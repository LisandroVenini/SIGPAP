<?php

namespace App\Modules\Produccion\Models;

use Database\Factories\PlanDeMantenimientoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanDeMantenimiento extends Model
{
    use HasFactory;

    protected static function newFactory(): PlanDeMantenimientoFactory
    {
        return PlanDeMantenimientoFactory::new();
    }

    /**
     * @var string
     */
    protected $table = 'planes_de_mantenimiento';

    /**
     * @var array<int, string>
     */
    protected $fillable = ['maquinaria_id', 'umbral_uso', 'descripcion'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'umbral_uso' => 'decimal:2',
        ];
    }

    public function maquinaria(): BelongsTo
    {
        return $this->belongsTo(Maquinaria::class);
    }

    public function scopeBuscar(Builder $query, string $termino): void
    {
        $termino = addcslashes($termino, '%_\\');

        $query->where(function (Builder $query) use ($termino): void {
            $query->whereHas('maquinaria', function (Builder $query) use ($termino): void {
                $query->where('nombre', 'like', "%{$termino}%");
            })->orWhere('descripcion', 'like', "%{$termino}%");
        });
    }
}
