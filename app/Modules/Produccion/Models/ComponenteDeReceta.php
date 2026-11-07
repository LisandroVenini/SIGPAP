<?php

namespace App\Modules\Produccion\Models;

use Database\Factories\ComponenteDeRecetaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComponenteDeReceta extends Model
{
    use HasFactory;

    protected static function newFactory(): ComponenteDeRecetaFactory
    {
        return ComponenteDeRecetaFactory::new();
    }

    /**
     * @var string
     */
    protected $table = 'componentes_de_receta';

    /**
     * @var array<int, string>
     */
    protected $fillable = ['receta_id', 'articulo_id', 'cantidad'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
        ];
    }

    public function receta(): BelongsTo
    {
        return $this->belongsTo(Receta::class);
    }
}
