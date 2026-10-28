<?php

namespace Tests\Feature\Modules\Usuarios;

use App\Modules\Usuarios\Enums\Rol;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_every_domain_role(): void
    {
        // Arrange / Act
        $this->seed(RoleSeeder::class);

        // Assert
        foreach (Rol::cases() as $rol) {
            $this->assertDatabaseHas('roles', [
                'name' => $rol->value,
                'guard_name' => 'web',
            ]);
        }
    }

    public function test_it_is_idempotent(): void
    {
        // Arrange / Act
        $this->seed(RoleSeeder::class);
        $this->seed(RoleSeeder::class);

        // Assert
        $this->assertSame(count(Rol::cases()), Role::where('guard_name', 'web')->count());
    }
}
