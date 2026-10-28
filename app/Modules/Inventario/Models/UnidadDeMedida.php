<?php

namespace App\Modules\Inventario\Models;

use Database\Factories\UnidadDeMedidaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnidadDeMedida extends Model
{
    use HasFactory;

    protected static function newFactory(): UnidadDeMedidaFactory
    {
        return UnidadDeMedidaFactory::new();
    }

    /**
     * @var string
     */
    protected $table = 'unidades_de_medida';

    /**
     * @var array<int, string>
     */
    protected $fillable = ['nombre', 'factor_conversion'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'factor_conversion' => 'decimal:6',
        ];
    }

    public function articulos(): HasMany
    {
        return $this->hasMany(Articulo::class);
    }
}
