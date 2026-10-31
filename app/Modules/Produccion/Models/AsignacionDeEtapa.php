<?php

namespace App\Modules\Produccion\Models;

use Database\Factories\AsignacionDeEtapaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsignacionDeEtapa extends Model
{
    use HasFactory;

    protected static function newFactory(): AsignacionDeEtapaFactory
    {
        return AsignacionDeEtapaFactory::new();
    }

    /**
     * @var string
     */
    protected $table = 'asignaciones_de_etapa';

    /**
     * @var array<int, string>
     */
    protected $fillable = ['articulo_id', 'etapa_id', 'tiempo_por_unidad', 'orden'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tiempo_por_unidad' => 'decimal:2',
            'orden' => 'integer',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdenadas($query): Builder
    {
        return $query->orderBy('orden');
    }

    public function etapa(): BelongsTo
    {
        return $this->belongsTo(Etapa::class);
    }
}
