<?php

namespace App\Shared\Alertas\Models;

use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertaLectura extends Model
{
    /**
     * @var string
     */
    protected $table = 'alerta_lecturas';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['alerta_id', 'usuario_id', 'leida_en'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'leida_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Alerta, $this>
     */
    public function alerta(): BelongsTo
    {
        return $this->belongsTo(Alerta::class);
    }

    /**
     * @return BelongsTo<Usuario, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
