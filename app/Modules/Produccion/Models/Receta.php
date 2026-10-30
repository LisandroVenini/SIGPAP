<?php

namespace App\Modules\Produccion\Models;

use Database\Factories\RecetaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Receta extends Model
{
    use HasFactory;

    protected static function newFactory(): RecetaFactory
    {
        return RecetaFactory::new();
    }

    /**
     * @var string
     */
    protected $table = 'recetas';

    /**
     * @var array<int, string>
     */
    protected $fillable = ['articulo_id'];

    public function componentesDeReceta(): HasMany
    {
        return $this->hasMany(ComponenteDeReceta::class);
    }
}
