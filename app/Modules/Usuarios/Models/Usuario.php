<?php

namespace App\Modules\Usuarios\Models;

use Database\Factories\UsuarioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['nombre', 'email', 'clave_acceso'])]
#[Hidden(['clave_acceso', 'remember_token'])]
class Usuario extends Authenticatable
{
    /** @use HasFactory<UsuarioFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The table associated with the model.
     *
     * Declared explicitly because Laravel's inflector is English-only and
     * mishandles Spanish plurals.
     *
     * @var string
     */
    protected $table = 'usuarios';

    /**
     * Emails are stored lowercased so login and uniqueness are case-insensitive.
     */
    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (string $valor): string => Str::lower(trim($valor)),
        );
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): UsuarioFactory
    {
        return UsuarioFactory::new();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'clave_acceso' => 'hashed',
        ];
    }

    /**
     * Get the password for authentication using the domain column name.
     */
    public function getAuthPassword(): string
    {
        return $this->clave_acceso;
    }

    /**
     * Get the name of the password attribute for authentication.
     */
    public function getAuthPasswordName(): string
    {
        return 'clave_acceso';
    }
}
