<?php

namespace Database\Factories;

use App\Modules\Usuarios\Enums\EstadoUsuario;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Usuario>
 */
class UsuarioFactory extends Factory
{
    /**
     * The model that this factory creates.
     *
     * @var class-string<Usuario>
     */
    protected $model = Usuario::class;

    /**
     * Define the model's default state.
     *
     * The plain value is hashed by the model's `clave_acceso` cast.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'clave_acceso' => 'password',
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * The column default is not read back into the model, so set it explicitly.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Usuario $usuario): void {
            $usuario->estado ??= EstadoUsuario::Activo->value;
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
