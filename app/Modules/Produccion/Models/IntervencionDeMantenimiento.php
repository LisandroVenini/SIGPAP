<?php

namespace App\Modules\Produccion\Models;

use Database\Factories\IntervencionDeMantenimientoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntervencionDeMantenimiento extends Model
{
    use HasFactory;

    protected static function newFactory(): IntervencionDeMantenimientoFactory
    {
        return IntervencionDeMantenimientoFactory::new();
    }

    /**
     * @var string
     */
    protected $table = 'intervenciones_de_mantenimiento';

    /**
     * @var array<int, string>
     */
    protected $fillable = ['maquinaria_id', 'fecha', 'tipo', 'responsable', 'observaciones'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    public function scopeOrdenadas(Builder $query): void
    {
        $query->orderByDesc('fecha');
    }

    public function scopeDeMaquinaria(Builder $query, int $maquinariaId): void
    {
        $query->where('maquinaria_id', $maquinariaId);
    }

    public function maquinaria(): BelongsTo
    {
        return $this->belongsTo(Maquinaria::class);
    }
}
