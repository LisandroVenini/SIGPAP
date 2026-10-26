<?php

namespace Database\Factories;

use App\Modules\Compras\Models\DesempenoProveedor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DesempenoProveedor>
 */
class DesempenoProveedorFactory extends Factory
{
    /**
     * @var class-string<DesempenoProveedor>
     */
    protected $model = DesempenoProveedor::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'proveedor_id' => ProveedorFactory::new(),
            'orden_de_compra_id' => null,
            'tiempo_entrega_real' => 4,
            'fecha_registro' => now(),
        ];
    }
}
