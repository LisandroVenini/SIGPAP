<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Inventario\Enums\EstadoArticulo;
use App\Modules\Inventario\Enums\OrigenArticulo;
use App\Modules\Inventario\Enums\TipoArticulo;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Produccion\Models\AsignacionDeEtapa;
use App\Modules\Produccion\Models\Etapa;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class HojaDeRutaControllerTest extends TestCase
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

    public function test_guests_are_redirected_to_the_login_screen(): void
    {
        // Act
        $response = $this->get('/produccion/hoja-de-ruta');

        // Assert
        $response->assertRedirect('/login');
    }

    public function test_usuarios_without_role_are_forbidden(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->get('/produccion/hoja-de-ruta');

        // Assert
        $response->assertForbidden();
    }

    public function test_a_supervisor_can_list_fabricado_products(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $this->productoFabricado();

        // Act
        $response = $this->actingAs($usuario)->get('/produccion/hoja-de-ruta');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Produccion/HojaDeRuta/Index')
            ->has('productos', 1));
    }

    public function test_a_supervisor_can_view_the_hoja_de_ruta_of_a_product(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $producto = $this->productoFabricado();
        $etapa = Etapa::factory()->create();
        AsignacionDeEtapa::factory()->create([
            'articulo_id' => $producto->id,
            'etapa_id' => $etapa->id,
            'tiempo_por_unidad' => 8,
        ]);

        // Act
        $response = $this->actingAs($usuario)->get("/produccion/hoja-de-ruta/{$producto->id}");

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Produccion/HojaDeRuta/Detalle')
            ->where('producto.id', $producto->id)
            ->has('asignadas', 1)
            ->has('disponibles'));
    }

    public function test_a_product_that_is_not_fabricado_returns_not_found(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $producto = Articulo::factory()->create(['tipo' => TipoArticulo::MateriaPrima->value]);

        // Act
        $response = $this->actingAs($usuario)->get("/produccion/hoja-de-ruta/{$producto->id}");

        // Assert
        $response->assertNotFound();
    }

    public function test_a_supervisor_can_assign_an_etapa_to_a_product(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $producto = $this->productoFabricado();
        $etapa = Etapa::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->post("/produccion/hoja-de-ruta/{$producto->id}/etapas", [
            'etapa_id' => $etapa->id,
            'tiempo_por_unidad' => 15,
        ]);

        // Assert
        $response->assertRedirect("/produccion/hoja-de-ruta/{$producto->id}");
        $this->assertDatabaseHas('asignaciones_de_etapa', [
            'articulo_id' => $producto->id,
            'etapa_id' => $etapa->id,
        ]);
    }

    public function test_a_supervisor_can_desassign_an_etapa_from_a_product(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $producto = $this->productoFabricado();
        $etapa = Etapa::factory()->create();
        AsignacionDeEtapa::factory()->create([
            'articulo_id' => $producto->id,
            'etapa_id' => $etapa->id,
        ]);

        // Act
        $response = $this->actingAs($usuario)->delete("/produccion/hoja-de-ruta/{$producto->id}/etapas/{$etapa->id}");

        // Assert
        $response->assertRedirect("/produccion/hoja-de-ruta/{$producto->id}");
        $this->assertDatabaseMissing('asignaciones_de_etapa', [
            'articulo_id' => $producto->id,
            'etapa_id' => $etapa->id,
        ]);
    }
}
