<?php

namespace App\Modules\Produccion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntervencionDeMantenimiento extends Model
{
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

    public function maquinaria(): BelongsTo
    {
        return $this->belongsTo(Maquinaria::class);
    }
}
