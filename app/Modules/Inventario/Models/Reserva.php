<?php

namespace App\Modules\Inventario\Models;

use Database\Factories\ReservaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reserva extends Model
{
    use HasFactory;

    protected static function newFactory(): ReservaFactory
    {
        return ReservaFactory::new();
    }

    /**
     * @var string
     */
    protected $table = 'reservas';

    /**
     * @var array<int, string>
     */
    protected $fillable = ['articulo_id', 'orden_de_produccion_id', 'cantidad', 'estado'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
        ];
    }

    public function articulo(): BelongsTo
    {
        return $this->belongsTo(Articulo::class);
    }
}
