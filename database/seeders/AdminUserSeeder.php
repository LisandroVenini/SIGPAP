<?php

namespace Database\Seeders;

use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $configuredPassword = config('sigpap.admin.password');
        $password = $configuredPassword ?: Str::password(16);

        $admin = Usuario::firstOrCreate(
            ['email' => config('sigpap.admin.email')],
            ['nombre' => 'Administrador', 'clave_acceso' => $password],
        );

        $admin->assignRole(Rol::Administrador->value);

        if ($admin->wasRecentlyCreated && ! $configuredPassword) {
            $this->command?->warn("Admin creado: {$admin->email} · contraseña: {$password}");
        }
    }
}
