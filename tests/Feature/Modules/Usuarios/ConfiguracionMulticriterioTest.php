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

class ConfiguracionMulticriterioTest extends TestCase
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

    public function test_the_seeder_registers_the_multicriteria_parameters_with_their_defaults(): void
    {
        // Act
        $this->seed(ParametroGlobalSeeder::class);

        // Assert
        $this->assertDatabaseHas('parametros_globales', [
            'nombre' => 'compras_multicriterio_peso_precio',
            'valor' => '0.4',
            'categoria' => 'Compras',
        ]);
        $this->assertDatabaseHas('parametros_globales', [
            'nombre' => 'compras_multicriterio_peso_entrega',
            'valor' => '0.3',
            'categoria' => 'Compras',
        ]);
        $this->assertDatabaseHas('parametros_globales', [
            'nombre' => 'compras_multicriterio_peso_confiabilidad',
            'valor' => '0.3',
            'categoria' => 'Compras',
        ]);
        $this->assertDatabaseHas('parametros_globales', [
            'nombre' => 'compras_multicriterio_confiabilidad_sin_historial',
            'valor' => '50',
            'categoria' => 'Compras',
        ]);
        $this->assertDatabaseHas('parametros_globales', [
            'nombre' => 'compras_multicriterio_penalizar_cobertura',
            'valor' => '1',
            'categoria' => 'Compras',
        ]);
    }

    public function test_reseeding_keeps_customised_multicriteria_values(): void
    {
        // Arrange
        $this->seed(ParametroGlobalSeeder::class);
        ParametroGlobal::where('nombre', 'compras_multicriterio_peso_precio')->update(['valor' => '0.6']);

        // Act
        $this->seed(ParametroGlobalSeeder::class);

        // Assert
        $this->assertSame('0.6', $this->valorDe('compras_multicriterio_peso_precio'));
    }

    public function test_an_administrador_can_update_the_multicriteria_parameters(): void
    {
        // Arrange
        $admin = $this->admin();

        // Act
        $response = $this->actingAs($admin)->put('/usuarios/configuracion', [
            'parametros' => [
                'compras_multicriterio_peso_precio' => '0.5',
                'compras_multicriterio_peso_entrega' => '0.25',
                'compras_multicriterio_peso_confiabilidad' => '0.25',
                'compras_multicriterio_confiabilidad_sin_historial' => '70',
                'compras_multicriterio_penalizar_cobertura' => '0',
            ],
        ]);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertSame('0.5', $this->valorDe('compras_multicriterio_peso_precio'));
        $this->assertSame('0.25', $this->valorDe('compras_multicriterio_peso_entrega'));
        $this->assertSame('0.25', $this->valorDe('compras_multicriterio_peso_confiabilidad'));
        $this->assertSame('70', $this->valorDe('compras_multicriterio_confiabilidad_sin_historial'));
        $this->assertSame('0', $this->valorDe('compras_multicriterio_penalizar_cobertura'));
    }

    public function test_multicriteria_parameters_are_optional(): void
    {
        // Arrange
        $admin = $this->admin();

        // Act
        $response = $this->actingAs($admin)->put('/usuarios/configuracion', [
            'parametros' => ['compras_multicriterio_peso_entrega' => '0.35'],
        ]);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertSame('0.35', $this->valorDe('compras_multicriterio_peso_entrega'));
        $this->assertSame('0.4', $this->valorDe('compras_multicriterio_peso_precio'));
    }

    public function test_a_weight_outside_the_zero_one_range_is_rejected(): void
    {
        // Arrange
        $admin = $this->admin();

        // Act
        $response = $this->actingAs($admin)->put('/usuarios/configuracion', [
            'parametros' => ['compras_multicriterio_peso_precio' => '1.5'],
        ]);

        // Assert
        $response->assertSessionHasErrors('parametros.compras_multicriterio_peso_precio');
        $this->assertSame('0.4', $this->valorDe('compras_multicriterio_peso_precio'));
    }

    public function test_a_non_numeric_weight_is_rejected(): void
    {
        // Arrange
        $admin = $this->admin();

        // Act
        $response = $this->actingAs($admin)->put('/usuarios/configuracion', [
            'parametros' => ['compras_multicriterio_peso_entrega' => 'mucho'],
        ]);

        // Assert
        $response->assertSessionHasErrors('parametros.compras_multicriterio_peso_entrega');
        $this->assertSame('0.3', $this->valorDe('compras_multicriterio_peso_entrega'));
    }

    public function test_a_reliability_outside_the_zero_hundred_range_is_rejected(): void
    {
        // Arrange
        $admin = $this->admin();

        // Act
        $menor = $this->actingAs($admin)->put('/usuarios/configuracion', [
            'parametros' => ['compras_multicriterio_confiabilidad_sin_historial' => '-1'],
        ]);
        $mayor = $this->actingAs($admin)->put('/usuarios/configuracion', [
            'parametros' => ['compras_multicriterio_confiabilidad_sin_historial' => '101'],
        ]);

        // Assert
        $menor->assertSessionHasErrors('parametros.compras_multicriterio_confiabilidad_sin_historial');
        $mayor->assertSessionHasErrors('parametros.compras_multicriterio_confiabilidad_sin_historial');
        $this->assertSame('50', $this->valorDe('compras_multicriterio_confiabilidad_sin_historial'));
    }

    public function test_the_coverage_penalty_flag_only_accepts_zero_or_one(): void
    {
        // Arrange
        $admin = $this->admin();

        // Act
        $response = $this->actingAs($admin)->put('/usuarios/configuracion', [
            'parametros' => ['compras_multicriterio_penalizar_cobertura' => '2'],
        ]);

        // Assert
        $response->assertSessionHasErrors('parametros.compras_multicriterio_penalizar_cobertura');
        $this->assertSame('1', $this->valorDe('compras_multicriterio_penalizar_cobertura'));
    }
}
