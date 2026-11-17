<?php

namespace Tests\Feature\Modules\Usuarios;

use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\ParametroGlobal;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\ParametroGlobalSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfiguracionRankingTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class, ParametroGlobalSeeder::class]);

        $admin = Usuario::factory()->create();
        $admin->assignRole(Rol::Administrador->value);

        return $admin;
    }

    private function valorDe(string $clave): string
    {
        return (string) ParametroGlobal::where('nombre', $clave)->value('valor');
    }

    public function test_the_seeder_registers_the_ranking_parameters_with_their_defaults(): void
    {
        // Act
        $this->seed(ParametroGlobalSeeder::class);

        // Assert
        $this->assertDatabaseHas('parametros_globales', [
            'nombre' => 'compras_ranking_peso_perfecta',
            'valor' => '1',
            'categoria' => 'Compras',
        ]);
        $this->assertDatabaseHas('parametros_globales', [
            'nombre' => 'compras_ranking_peso_retraso',
            'valor' => '0.6',
            'categoria' => 'Compras',
        ]);
        $this->assertDatabaseHas('parametros_globales', [
            'nombre' => 'compras_ranking_peso_faltante',
            'valor' => '0.3',
            'categoria' => 'Compras',
        ]);
        $this->assertDatabaseHas('parametros_globales', [
            'nombre' => 'compras_ranking_peso_rotura',
            'valor' => '0',
            'categoria' => 'Compras',
        ]);
        $this->assertDatabaseHas('parametros_globales', [
            'nombre' => 'compras_ranking_ventana_meses',
            'valor' => '12',
            'categoria' => 'Compras',
        ]);
    }

    public function test_reseeding_keeps_customised_values(): void
    {
        // Arrange
        $this->seed([ParametroGlobalSeeder::class]);
        ParametroGlobal::where('nombre', 'compras_ranking_peso_retraso')->update(['valor' => '0.45']);

        // Act
        $this->seed([ParametroGlobalSeeder::class]);

        // Assert
        $this->assertSame('0.45', $this->valorDe('compras_ranking_peso_retraso'));
    }

    public function test_an_administrador_can_update_the_ranking_parameters(): void
    {
        // Arrange
        $admin = $this->admin();

        // Act
        $response = $this->actingAs($admin)->put('/usuarios/configuracion', [
            'parametros' => [
                'compras_ranking_peso_perfecta' => '0.9',
                'compras_ranking_peso_retraso' => '0.5',
                'compras_ranking_peso_faltante' => '0.2',
                'compras_ranking_peso_rotura' => '0.1',
                'compras_ranking_ventana_meses' => '6',
            ],
        ]);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertSame('0.9', $this->valorDe('compras_ranking_peso_perfecta'));
        $this->assertSame('0.5', $this->valorDe('compras_ranking_peso_retraso'));
        $this->assertSame('0.2', $this->valorDe('compras_ranking_peso_faltante'));
        $this->assertSame('0.1', $this->valorDe('compras_ranking_peso_rotura'));
        $this->assertSame('6', $this->valorDe('compras_ranking_ventana_meses'));
    }

    public function test_ranking_parameters_are_optional(): void
    {
        // Arrange
        $admin = $this->admin();

        // Act
        $response = $this->actingAs($admin)->put('/usuarios/configuracion', [
            'parametros' => [
                'compras_ranking_ventana_meses' => '24',
            ],
        ]);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertSame('24', $this->valorDe('compras_ranking_ventana_meses'));
        $this->assertSame('1', $this->valorDe('compras_ranking_peso_perfecta'));
    }

    public function test_other_configuration_parameters_keep_their_previous_behavior(): void
    {
        // Arrange
        $admin = $this->admin();

        // Act
        $response = $this->actingAs($admin)->put('/usuarios/configuracion', [
            'parametros' => ['compras_vigencia_cotizacion_dias' => '21'],
        ]);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertSame('21', $this->valorDe('compras_vigencia_cotizacion_dias'));
    }

    public function test_a_weight_above_one_is_rejected(): void
    {
        // Arrange
        $admin = $this->admin();

        // Act
        $response = $this->actingAs($admin)->put('/usuarios/configuracion', [
            'parametros' => ['compras_ranking_peso_perfecta' => '1.5'],
        ]);

        // Assert
        $response->assertSessionHasErrors('parametros.compras_ranking_peso_perfecta');
        $this->assertSame('1', $this->valorDe('compras_ranking_peso_perfecta'));
    }

    public function test_a_negative_weight_is_rejected(): void
    {
        // Arrange
        $admin = $this->admin();

        // Act
        $response = $this->actingAs($admin)->put('/usuarios/configuracion', [
            'parametros' => ['compras_ranking_peso_rotura' => '-0.1'],
        ]);

        // Assert
        $response->assertSessionHasErrors('parametros.compras_ranking_peso_rotura');
        $this->assertSame('0', $this->valorDe('compras_ranking_peso_rotura'));
    }

    public function test_a_non_numeric_weight_is_rejected(): void
    {
        // Arrange
        $admin = $this->admin();

        // Act
        $response = $this->actingAs($admin)->put('/usuarios/configuracion', [
            'parametros' => ['compras_ranking_peso_faltante' => 'mucho'],
        ]);

        // Assert
        $response->assertSessionHasErrors('parametros.compras_ranking_peso_faltante');
        $this->assertSame('0.3', $this->valorDe('compras_ranking_peso_faltante'));
    }

    public function test_a_window_outside_the_allowed_range_is_rejected(): void
    {
        // Arrange
        $admin = $this->admin();

        // Act
        $menor = $this->actingAs($admin)->put('/usuarios/configuracion', [
            'parametros' => ['compras_ranking_ventana_meses' => '0'],
        ]);
        $mayor = $this->actingAs($admin)->put('/usuarios/configuracion', [
            'parametros' => ['compras_ranking_ventana_meses' => '61'],
        ]);

        // Assert
        $menor->assertSessionHasErrors('parametros.compras_ranking_ventana_meses');
        $mayor->assertSessionHasErrors('parametros.compras_ranking_ventana_meses');
        $this->assertSame('12', $this->valorDe('compras_ranking_ventana_meses'));
    }

    public function test_a_non_integer_window_is_rejected(): void
    {
        // Arrange
        $admin = $this->admin();

        // Act
        $response = $this->actingAs($admin)->put('/usuarios/configuracion', [
            'parametros' => ['compras_ranking_ventana_meses' => '12.5'],
        ]);

        // Assert
        $response->assertSessionHasErrors('parametros.compras_ranking_ventana_meses');
        $this->assertSame('12', $this->valorDe('compras_ranking_ventana_meses'));
    }
}
