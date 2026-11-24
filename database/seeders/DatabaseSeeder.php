<?php

namespace Database\Seeders;

use App\Modules\Produccion\Database\Seeders\ProduccionSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            ParametroGlobalSeeder::class,
            UnidadDeMedidaSeeder::class,
            AdminUserSeeder::class,
            SistemaUserSeeder::class,
            ReglaDeAlertaSeeder::class,
            // Module demo data. Guarded to local/testing, and it also seeds the
            // inventory catalog and the demo supervisor it depends on.
            ProduccionSeeder::class,
        ]);
    }
}
