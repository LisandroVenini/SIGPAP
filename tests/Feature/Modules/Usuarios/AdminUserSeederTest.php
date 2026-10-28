<?php

namespace Tests\Feature\Modules\Usuarios;

use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_does_not_create_an_admin_outside_local(): void
    {
        // Arrange
        $this->app['env'] = 'production';

        // Act
        (new AdminUserSeeder)->run();

        // Assert
        $this->assertDatabaseMissing('usuarios', ['email' => config('sigpap.admin.email')]);
    }

    public function test_it_creates_the_admin_in_local_with_the_configured_password(): void
    {
        // Arrange
        $this->app['env'] = 'local';
        $this->seed(RoleSeeder::class);
        config([
            'sigpap.admin.email' => 'admin@test.local',
            'sigpap.admin.password' => 'secret-password',
        ]);

        // Act
        $this->seed(AdminUserSeeder::class);

        // Assert
        $admin = Usuario::where('email', 'admin@test.local')->firstOrFail();
        $this->assertTrue(Hash::check('secret-password', $admin->clave_acceso));
        $this->assertTrue($admin->hasRole(Rol::Administrador->value));
    }

    public function test_it_is_idempotent(): void
    {
        // Arrange
        $this->app['env'] = 'local';
        $this->seed(RoleSeeder::class);
        config([
            'sigpap.admin.email' => 'admin@test.local',
            'sigpap.admin.password' => 'secret-password',
        ]);

        // Act
        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class);

        // Assert
        $this->assertSame(1, Usuario::where('email', 'admin@test.local')->count());
    }

    public function test_it_generates_a_password_when_none_is_configured(): void
    {
        // Arrange
        $this->app['env'] = 'local';
        $this->seed(RoleSeeder::class);
        config([
            'sigpap.admin.email' => 'admin@test.local',
            'sigpap.admin.password' => null,
        ]);

        // Act
        $this->artisan('db:seed', ['--class' => AdminUserSeeder::class])
            ->expectsOutputToContain('contraseña')
            ->assertSuccessful();

        // Assert
        $admin = Usuario::where('email', 'admin@test.local')->firstOrFail();
        $this->assertNotEmpty($admin->clave_acceso);
    }
}
