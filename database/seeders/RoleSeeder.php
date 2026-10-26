<?php

namespace Database\Seeders;

use App\Modules\Usuarios\Enums\Rol;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Rol::cases() as $rol) {
            Role::firstOrCreate(['name' => $rol->value, 'guard_name' => 'web']);
        }
    }
}
