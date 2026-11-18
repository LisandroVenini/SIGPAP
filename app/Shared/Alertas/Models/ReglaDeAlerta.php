<?php

namespace App\Shared\Alertas\Models;

use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role;

class ReglaDeAlerta extends Model
{
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'reglas_de_alerta';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'descripcion',
        'tipo',
        'modulo',
        'severidad',
        'origen_tipo',
        'condiciones',
        'mensaje',
        'activa',
        'creada_por',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'condiciones' => 'array',
            'activa' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'regla_de_alerta_rol');
    }

    /**
     * @return BelongsTo<Usuario, $this>
     */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'creada_por');
    }

    /**
     * @return HasMany<Alerta, $this>
     */
    public function alertas(): HasMany
    {
        return $this->hasMany(Alerta::class, 'regla_id');
    }
}
