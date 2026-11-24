<?php

namespace Database\Seeders;

use App\Modules\Usuarios\Services\UsuariosService;
use Illuminate\Database\Seeder;

class SistemaUserSeeder extends Seeder
{
    /**
     * Technical actor for automated decisions (CAM-001). No role: no access.
     */
    public function run(): void
    {
        app(UsuariosService::class)->usuarioSistema();
    }
}
