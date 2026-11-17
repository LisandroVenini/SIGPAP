<?php

namespace Tests\Feature\Modules\Inventario;

use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Models\UnidadDeMedida;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class UnidadDeMedidaControllerTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioConRol(Rol $rol): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole($rol->value);

        return $usuario;
    }

    public function test_guests_are_redirected_to_the_login_screen(): void
    {
        // Act
        $response = $this->get('/inventario/unidades-de-medida');

        // Assert
        $response->assertRedirect('/login');
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->get('/inventario/unidades-de-medida');

        // Assert
        $response->assertForbidden();
    }

    public function test_a_stock_manager_can_list_the_units(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoStock);
        UnidadDeMedida::factory()->create(['nombre' => 'Unidad']);

        // Act
        $response = $this->actingAs($usuario)->get('/inventario/unidades-de-medida');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Inventario/UnidadesDeMedida/Index')
            ->has('unidades', 1)
            ->where('unidades.0.nombre', 'Unidad'));
    }

    public function test_a_stock_manager_can_create_a_unit(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoStock);

        // Act
        $response = $this->actingAs($usuario)->post('/inventario/unidades-de-medida', [
            'nombre' => 'Metro cuadrado',
        ]);

        // Assert
        $response->assertRedirect('/inventario/unidades-de-medida');
        $this->assertDatabaseHas('unidades_de_medida', ['nombre' => 'Metro cuadrado']);
    }

    public function test_a_duplicate_name_is_rejected_ignoring_case(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoStock);
        UnidadDeMedida::factory()->create(['nombre' => 'Litro']);

        // Act
        $response = $this->actingAs($usuario)->post('/inventario/unidades-de-medida', [
            'nombre' => 'LITRO',
        ]);

        // Assert
        $response->assertSessionHasErrors('nombre');
        $this->assertDatabaseCount('unidades_de_medida', 1);
    }

    public function test_a_stock_manager_can_update_a_unit(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoStock);
        $unidad = UnidadDeMedida::factory()->create(['nombre' => 'Litro']);

        // Act
        $response = $this->actingAs($usuario)->put("/inventario/unidades-de-medida/{$unidad->id}", [
            'nombre' => 'Litro',
        ]);

        // Assert
        $response->assertRedirect('/inventario/unidades-de-medida');
        $this->assertDatabaseHas('unidades_de_medida', ['id' => $unidad->id, 'nombre' => 'Litro']);
    }

    public function test_a_unit_in_use_cannot_be_deleted(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoStock);
        $unidad = UnidadDeMedida::factory()->create();
        Articulo::factory()->create(['unidad_de_medida_id' => $unidad->id]);

        // Act
        $response = $this->actingAs($usuario)->delete("/inventario/unidades-de-medida/{$unidad->id}");

        // Assert
        $response->assertSessionHasErrors('unidad');
        $this->assertDatabaseHas('unidades_de_medida', ['id' => $unidad->id]);
    }

    public function test_an_unused_unit_can_be_deleted(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoStock);
        $unidad = UnidadDeMedida::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->delete("/inventario/unidades-de-medida/{$unidad->id}");

        // Assert
        $response->assertRedirect('/inventario/unidades-de-medida');
        $this->assertDatabaseMissing('unidades_de_medida', ['id' => $unidad->id]);
    }
}
