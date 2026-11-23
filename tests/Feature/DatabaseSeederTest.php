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
        $this->assertDatabaseHas('parametros_globales', ['nombre' => 'compras_vigencia_cotizacion_dias']);
        $this->assertDatabaseHas('usuarios', ['email' => config('sigpap.admin.email')]);
    }

    public function test_it_seeds_the_module_demo_data(): void
    {
        // Arrange
        $this->app['env'] = 'local';

        // Act
        $this->seed(DatabaseSeeder::class);

        // Assert
        $this->assertDatabaseHas('usuarios', ['email' => 'supervisor.demo@sigpap.local']);
        $this->assertDatabaseHas('articulos', ['codigo_sku' => 'MP-001']);
        $this->assertDatabaseHas('etapas', ['nombre' => 'Corte']);
        $this->assertDatabaseCount('ordenes_de_produccion', 15);
    }
}
