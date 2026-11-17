<?php

namespace Database\Seeders;

use App\Modules\Inventario\Models\UnidadDeMedida;
use Illuminate\Database\Seeder;

class UnidadDeMedidaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $unidades = [
            'Unidad',
            'Kilogramo',
            'Gramo',
            'Metro',
            'Metro cuadrado',
            'Litro',
            'Mililitro',
        ];

        foreach ($unidades as $nombre) {
            UnidadDeMedida::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
