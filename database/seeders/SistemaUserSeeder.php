<?php

namespace Database\Seeders;

use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SistemaUserSeeder extends Seeder
{
    /**
     * Technical actor for automated decisions (CAM-001). No role: no access.
     */
    public function run(): void
    {
        Usuario::query()->firstOrCreate(
            ['email' => 'sistema@sigpap.local'],
            ['nombre' => 'Sistema', 'clave_acceso' => Str::password(32)],
        );
    }
}
