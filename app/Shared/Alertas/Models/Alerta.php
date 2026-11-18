<?php

namespace App\Shared\Alertas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Role;

class Alerta extends Model
{
    /**
     * @var string
     */
    protected $table = 'alertas';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'regla_id',
        'tipo',
        'severidad',
        'origen_tipo',
        'fecha',
        'descripcion',
        'contexto',
        'clave_dedupe',
        'resuelta_en',
        'archivada_en',
        'origen',
        'orden_de_produccion_id',
        'articulo_id',
        'maquinaria_id',
        'orden_de_compra_id',
        'solicitud_de_cotizacion_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
            'resuelta_en' => 'datetime',
            'archivada_en' => 'datetime',
            'contexto' => 'array',
        ];
    }

    /**
     * @return BelongsTo<ReglaDeAlerta, $this>
     */
    public function regla(): BelongsTo
    {
        return $this->belongsTo(ReglaDeAlerta::class, 'regla_id');
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'alerta_rol');
    }

    /**
     * @return HasMany<AlertaLectura, $this>
     */
    public function lecturas(): HasMany
    {
        return $this->hasMany(AlertaLectura::class);
    }
}
