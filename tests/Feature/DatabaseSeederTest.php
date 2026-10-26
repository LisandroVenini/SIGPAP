<?php

namespace Tests\Feature;

use App\Modules\Usuarios\Enums\Rol;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_runs_the_foundation_seeders(): void
    {
        // Arrange
        $this->app['env'] = 'local';

        // Act
        $this->seed(DatabaseSeeder::class);

        // Assert
        $this->assertDatabaseHas('roles', ['name' => Rol::Administrador->value]);
        $this->assertDatabaseHas('parametros_globales', ['nombre' => 'umbral_tolerancia_precio']);
        $this->assertDatabaseHas('usuarios', ['email' => config('sigpap.admin.email')]);
    }
}
