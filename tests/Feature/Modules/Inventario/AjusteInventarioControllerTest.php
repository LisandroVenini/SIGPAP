<?php

namespace Tests\Feature\Modules\Inventario;

use App\Modules\Inventario\Enums\MotivoAjuste;
use App\Modules\Inventario\Enums\TipoMovimientoStock;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AjusteInventarioControllerTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioConRol(Rol $rol): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole($rol->value);

        return $usuario;
    }

    public function test_an_encargado_de_stock_can_adjust_stock(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoStock);
        $articulo = Articulo::factory()->create();
        $articulo->stock_fisico = 10;
        $articulo->save();

        // Act
        $response = $this->actingAs($usuario)->patch("/inventario/articulos/{$articulo->id}/ajuste", [
            'nuevo_stock' => 6,
            'motivo' => MotivoAjuste::Merma->value,
        ]);

        // Assert
        $response->assertRedirect('/inventario/ajustes');
        $this->assertSame(6.0, (float) $articulo->refresh()->stock_fisico);
        $this->assertDatabaseHas('movimientos_de_stock', [
            'articulo_id' => $articulo->id,
            'tipo' => TipoMovimientoStock::Ajuste->value,
            'cantidad' => -4,
        ]);
    }

    public function test_an_encargado_de_stock_can_view_the_adjust_page(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoStock);
        $articulo = Articulo::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->get('/inventario/ajustes');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Inventario/Ajuste')
            ->where('articulos.data.0.id', $articulo->id)
            ->has('motivos', 4));
    }

    public function test_usuarios_without_permission_cannot_view_the_adjust_page(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->get('/inventario/ajustes');

        // Assert
        $response->assertForbidden();
    }

    public function test_usuarios_without_permission_cannot_adjust_stock(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();
        $articulo = Articulo::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->patch("/inventario/articulos/{$articulo->id}/ajuste", [
            'nuevo_stock' => 1,
            'motivo' => MotivoAjuste::Rotura->value,
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_negative_stock_is_rejected(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoStock);
        $articulo = Articulo::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->patch("/inventario/articulos/{$articulo->id}/ajuste", [
            'nuevo_stock' => -1,
            'motivo' => MotivoAjuste::Rotura->value,
        ]);

        // Assert
        $response->assertSessionHasErrors('nuevo_stock');
    }

    public function test_a_missing_motivo_is_rejected(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoStock);
        $articulo = Articulo::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->patch("/inventario/articulos/{$articulo->id}/ajuste", [
            'nuevo_stock' => 1,
        ]);

        // Assert
        $response->assertSessionHasErrors('motivo');
    }
}
