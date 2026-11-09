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
        $inventario = Permission::firstOrCreate([
            'name' => 'inventario.gestionar',
            'guard_name' => 'web',
        ]);

        $produccion = Permission::firstOrCreate([
            'name' => 'produccion.gestionar',
            'guard_name' => 'web',
        ]);

        $compras = Permission::firstOrCreate([
            'name' => 'compras.gestionar',
            'guard_name' => 'web',
        ]);

        $auditoria = Permission::firstOrCreate([
            'name' => 'auditoria.consultar',
            'guard_name' => 'web',
        ]);

        Role::findByName(Rol::Administrador->value, 'web')->givePermissionTo([$inventario, $produccion, $compras, $auditoria]);
        Role::findByName(Rol::Gerente->value, 'web')->givePermissionTo($auditoria);
        Role::findByName(Rol::EncargadoStock->value, 'web')->givePermissionTo($inventario);
        Role::findByName(Rol::SupervisorProduccion->value, 'web')->givePermissionTo($produccion);
        Role::findByName(Rol::EncargadoCompras->value, 'web')->givePermissionTo($compras);
    }
}
