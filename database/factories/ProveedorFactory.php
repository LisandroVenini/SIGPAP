<?php

namespace Database\Factories;

use App\Modules\Compras\Models\Proveedor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proveedor>
 */
class ProveedorFactory extends Factory
{
    /**
     * @var class-string<Proveedor>
     */
    protected $model = Proveedor::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'razon_social' => fake()->company(),
            'cuit' => fake()->unique()->numerify('##-########-#'),
            'email' => fake()->safeEmail(),
            'telefono' => fake()->phoneNumber(),
            'estado' => 'Activo',
        ];
    }
}
