<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Inventario\Enums\EstadoArticulo;
use App\Modules\Inventario\Enums\OrigenArticulo;
use App\Modules\Inventario\Enums\TipoArticulo;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Produccion\Models\ComponenteDeReceta;
use App\Modules\Produccion\Models\Receta;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class RecetaControllerTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioConRol(Rol $rol): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole($rol->value);

        return $usuario;
    }

    private function productoFabricado(): Articulo
    {
        return Articulo::factory()->create([
            'tipo' => TipoArticulo::ProductoTerminado->value,
            'origen' => OrigenArticulo::Fabricado->value,
            'estado' => EstadoArticulo::Activo->value,
        ]);
    }

    private function insumoActivo(): Articulo
    {
        return Articulo::factory()->create([
            'tipo' => TipoArticulo::MateriaPrima->value,
            'origen' => null,
            'estado' => EstadoArticulo::Activo->value,
        ]);
    }

    public function test_guests_are_redirected_to_the_login_screen(): void
    {
        // Act
        $response = $this->post('/produccion/hoja-de-ruta/1/receta/componentes');

        // Assert
        $response->assertRedirect('/login');
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();
        $producto = $this->productoFabricado();
        $insumo = $this->insumoActivo();

        // Act
        $response = $this->actingAs($usuario)->post(
            "/produccion/hoja-de-ruta/{$producto->id}/receta/componentes",
            [
                'articulo_insumo_id' => $insumo->id,
                'cantidad' => 5,
            ]
        );

        // Assert
        $response->assertForbidden();
    }

    public function test_a_supervisor_can_add_a_componente(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $producto = $this->productoFabricado();
        $insumo = $this->insumoActivo();

        // Act
        $response = $this->actingAs($usuario)->post(
            "/produccion/hoja-de-ruta/{$producto->id}/receta/componentes",
            [
                'articulo_insumo_id' => $insumo->id,
                'cantidad' => 7.5,
            ]
        );

        // Assert
        $response->assertRedirect("/produccion/hoja-de-ruta/{$producto->id}");
        $this->assertDatabaseHas('componentes_de_receta', [
            'articulo_id' => $insumo->id,
            'cantidad' => 7.5,
        ]);
    }

    public function test_cantidad_zero_returns_validation_error(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $producto = $this->productoFabricado();
        $insumo = $this->insumoActivo();

        // Act
        $response = $this->actingAs($usuario)->post(
            "/produccion/hoja-de-ruta/{$producto->id}/receta/componentes",
            [
                'articulo_insumo_id' => $insumo->id,
                'cantidad' => 0,
            ]
        );

        // Assert
        $response->assertSessionHasErrors('cantidad');
        $this->assertDatabaseCount('componentes_de_receta', 0);
    }

    public function test_a_supervisor_can_remove_a_componente(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $producto = $this->productoFabricado();
        $insumo = $this->insumoActivo();
        $receta = Receta::factory()->create(['articulo_id' => $producto->id]);
        $componente = ComponenteDeReceta::factory()->create([
            'receta_id' => $receta->id,
            'articulo_id' => $insumo->id,
            'cantidad' => 3,
        ]);

        // Act
        $response = $this->actingAs($usuario)->delete(
            "/produccion/hoja-de-ruta/{$producto->id}/receta/componentes/{$componente->id}"
        );

        // Assert
        $response->assertRedirect("/produccion/hoja-de-ruta/{$producto->id}");
        $this->assertDatabaseMissing('componentes_de_receta', ['id' => $componente->id]);
    }

    public function test_a_componente_from_another_product_cannot_be_removed(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $producto = $this->productoFabricado();
        $otroProducto = $this->productoFabricado();
        $insumo = $this->insumoActivo();
        $recetaAjena = Receta::factory()->create(['articulo_id' => $otroProducto->id]);
        $componente = ComponenteDeReceta::factory()->create([
            'receta_id' => $recetaAjena->id,
            'articulo_id' => $insumo->id,
            'cantidad' => 3,
        ]);

        // Act
        $response = $this->actingAs($usuario)->delete(
            "/produccion/hoja-de-ruta/{$producto->id}/receta/componentes/{$componente->id}"
        );

        // Assert
        $response->assertNotFound();
        $this->assertDatabaseHas('componentes_de_receta', ['id' => $componente->id]);
    }

    public function test_show_includes_receta_payload(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $producto = $this->productoFabricado();
        $insumo = $this->insumoActivo();
        $receta = Receta::factory()->create(['articulo_id' => $producto->id]);
        ComponenteDeReceta::factory()->create([
            'receta_id' => $receta->id,
            'articulo_id' => $insumo->id,
            'cantidad' => 2,
        ]);

        // Act
        $response = $this->actingAs($usuario)->get("/produccion/hoja-de-ruta/{$producto->id}");

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Produccion/HojaDeRuta/Detalle')
            ->has('receta')
            ->has('receta.componentes', 1)
            ->has('receta.disponibles'));
    }
}
