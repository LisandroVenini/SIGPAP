<?php

namespace App\Modules\Inventario\Models;

use Database\Factories\ArticuloFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Articulo extends Model
{
    use HasFactory;

    protected static function newFactory(): ArticuloFactory
    {
        return ArticuloFactory::new();
    }

    /**
     * @var string
     */
    protected $table = 'articulos';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'codigo_sku',
        'descripcion',
        'tipo',
        'origen',
        'stock_fisico',
        'stock_minimo',
        'stock_maximo',
        'fecha_vencimiento',
        'estado',
        'unidad_de_medida_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stock_fisico' => 'decimal:2',
            'stock_minimo' => 'decimal:2',
            'stock_maximo' => 'decimal:2',
            'fecha_vencimiento' => 'date',
        ];
    }

    public function scopeTipo(Builder $query, string $tipo): void
    {
        $query->where('tipo', $tipo);
    }

    public function scopeEstado(Builder $query, string $estado): void
    {
        $query->where('estado', $estado);
    }

    public function unidadDeMedida(): BelongsTo
    {
        return $this->belongsTo(UnidadDeMedida::class);
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }

    public function movimientosDeStock(): HasMany
    {
        return $this->hasMany(MovimientoDeStock::class);
    }
}
