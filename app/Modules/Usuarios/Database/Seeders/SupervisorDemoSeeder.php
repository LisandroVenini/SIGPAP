<?php

namespace App\Modules\Usuarios\Database\Seeders;

use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Database\Seeder;

/**
 * Demo supervisor used as the audit actor by other modules' seeders.
 */
class SupervisorDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $email = 'supervisor.demo@sigpap.local';

        if (Usuario::query()->where('email', $email)->exists()) {
            return;
        }

        // The factory owns the demo credential, so there is no hardcoded hash here.
        Usuario::factory()->create([
            'email' => $email,
            'nombre' => 'Supervisor Demo',
        ]);
    }
}
