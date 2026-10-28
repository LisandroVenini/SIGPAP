<?php

namespace Tests\Feature\Modules\Inventario;

use App\Modules\Inventario\Enums\TipoArticulo;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Models\UnidadDeMedida;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ArticuloControllerTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioConRol(Rol $rol): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole($rol->value);

        return $usuario;
    }

    private function unidad(): UnidadDeMedida
    {
        return UnidadDeMedida::factory()->create();
    }

    public function test_guests_are_redirected_to_the_login_screen(): void
    {
        // Act
        $response = $this->get('/inventario/articulos');

        // Assert
        $response->assertRedirect('/login');
    }

    public function test_usuarios_without_role_are_forbidden(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->get('/inventario/articulos');

        // Assert
        $response->assertForbidden();
    }

    public function test_an_encargado_de_stock_can_list_articulos(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoStock);

        // Act
        $response = $this->actingAs($usuario)->get('/inventario/articulos');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Inventario/Index')
            ->has('articulos.data')
            ->has('unidades')
            ->has('tipos')
            ->has('resumen')
        );
    }

    public function test_an_encargado_de_stock_can_create_an_articulo(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoStock);
        $unidad = $this->unidad();

        // Act
        $response = $this->actingAs($usuario)->post('/inventario/articulos', [
            'codigo_sku' => 'MP-001',
            'descripcion' => 'Madera de pino',
            'tipo' => TipoArticulo::MateriaPrima->value,
            'unidad_de_medida_id' => $unidad->id,
            'stock_minimo' => 10,
            'stock_maximo' => 100,
        ]);

        // Assert
        $response->assertRedirect('/inventario/articulos');
        $this->assertDatabaseHas('articulos', ['codigo_sku' => 'MP-001', 'estado' => 'Activo']);
        $this->assertDatabaseHas('eventos_de_auditoria', ['accion' => 'crear_articulo']);
    }

    public function test_a_duplicate_sku_is_rejected(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoStock);
        $unidad = $this->unidad();
        Articulo::factory()->create(['codigo_sku' => 'MP-001', 'unidad_de_medida_id' => $unidad->id]);

        // Act
        $response = $this->actingAs($usuario)->post('/inventario/articulos', [
            'codigo_sku' => 'MP-001',
            'descripcion' => 'Otra madera',
            'tipo' => TipoArticulo::MateriaPrima->value,
            'unidad_de_medida_id' => $unidad->id,
        ]);

        // Assert
        $response->assertSessionHasErrors('codigo_sku');
    }

    public function test_a_minimum_greater_than_maximum_is_rejected(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoStock);
        $unidad = $this->unidad();

        // Act
        $response = $this->actingAs($usuario)->post('/inventario/articulos', [
            'codigo_sku' => 'MP-002',
            'descripcion' => 'Madera',
            'tipo' => TipoArticulo::MateriaPrima->value,
            'unidad_de_medida_id' => $unidad->id,
            'stock_minimo' => 100,
            'stock_maximo' => 50,
        ]);

        // Assert
        $response->assertSessionHasErrors('stock_maximo');
    }

    public function test_baja_with_stock_requires_confirmation(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoStock);
        $articulo = Articulo::factory()->create();
        $articulo->stock_fisico = 5;
        $articulo->save();

        // Act
        $this->actingAs($usuario)
            ->patch("/inventario/articulos/{$articulo->id}/estado")
            ->assertSessionHasErrors('confirmar');

        // Assert
        $this->actingAs($usuario)
            ->patch("/inventario/articulos/{$articulo->id}/estado", ['confirmar' => true])
            ->assertRedirect('/inventario/articulos');

        $this->assertSame('Inactivo', $articulo->refresh()->estado);
    }
}
