<?php

namespace Database\Seeders;

use App\Modules\Usuarios\Enums\Rol;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'inventario.gestionar',
            'guard_name' => 'web',
        ]);

        Role::findByName(Rol::Administrador->value, 'web')->givePermissionTo($permission);
        Role::findByName(Rol::EncargadoStock->value, 'web')->givePermissionTo($permission);
    }
}
