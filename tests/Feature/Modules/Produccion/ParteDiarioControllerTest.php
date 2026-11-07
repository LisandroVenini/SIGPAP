<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Inventario\Enums\EstadoArticulo;
use App\Modules\Inventario\Enums\OrigenArticulo;
use App\Modules\Inventario\Enums\TipoArticulo;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Services\GestionReservasService;
use App\Modules\Produccion\Enums\EstadoMaquinaria;
use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use App\Modules\Produccion\Models\AsignacionDeEtapa;
use App\Modules\Produccion\Models\ComponenteDeReceta;
use App\Modules\Produccion\Models\Etapa;
use App\Modules\Produccion\Models\Maquinaria;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Models\Receta;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParteDiarioControllerTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioConRol(Rol $rol): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole($rol->value);

        return $usuario;
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    private function productoFabricado(array $atributos = []): Articulo
    {
        return Articulo::factory()->create($atributos + [
            'tipo' => TipoArticulo::ProductoTerminado->value,
            'origen' => OrigenArticulo::Fabricado->value,
            'estado' => EstadoArticulo::Activo->value,
        ]);
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    private function insumo(array $atributos = []): Articulo
    {
        return Articulo::factory()->create($atributos + [
            'tipo' => TipoArticulo::MateriaPrima->value,
            'estado' => EstadoArticulo::Activo->value,
        ]);
    }

    private function recetaConComponente(Articulo $producto, Articulo $insumo, float $cantidadPorUnidad): void
    {
        $receta = Receta::factory()->create(['articulo_id' => $producto->id]);
        ComponenteDeReceta::factory()->create([
            'receta_id' => $receta->id,
            'articulo_id' => $insumo->id,
            'cantidad' => $cantidadPorUnidad,
        ]);
    }

    private function ordenConHojaDeRuta(): array
    {
        $insumo = $this->insumo(['stock_fisico' => 100]);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 2);

        $corte = Etapa::factory()->create();
        AsignacionDeEtapa::factory()->create([
            'articulo_id' => $producto->id,
            'etapa_id' => $corte->id,
        ]);

        $orden = OrdenDeProduccion::factory()->create([
            'articulo_id' => $producto->id,
            'estado' => EstadoOrdenProduccion::EnCurso->value,
            'cantidad_requerida' => 10,
        ]);

        app(GestionReservasService::class)->reservar($orden->id, [$insumo->id => 20]);

        return [$orden, $corte];
    }

    public function test_guests_are_redirected_to_login(): void
    {
        // Arrange
        $orden = OrdenDeProduccion::factory()->create();

        // Act
        $response = $this->post("/produccion/ordenes-de-produccion/{$orden->id}/partes", [
            'etapa_id' => 1,
            'fecha' => '2026-11-01',
            'cantidad' => 1,
        ]);

        // Assert
        $response->assertRedirect('/login');
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();
        [$orden, $corte] = $this->ordenConHojaDeRuta();

        // Act
        $response = $this->actingAs($usuario)->post("/produccion/ordenes-de-produccion/{$orden->id}/partes", [
            'etapa_id' => $corte->id,
            'fecha' => '2026-11-01',
            'cantidad' => 1,
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_supervisor_can_register_a_parte(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        [$orden, $corte] = $this->ordenConHojaDeRuta();

        // Act
        $response = $this->actingAs($usuario)
            ->from('/produccion/ordenes-de-produccion')
            ->post("/produccion/ordenes-de-produccion/{$orden->id}/partes", [
                'etapa_id' => $corte->id,
                'maquinaria_id' => null,
                'fecha' => now()->toDateString(),
                'cantidad' => 5,
                'observaciones' => 'Inicio de corte',
            ]);

        // Assert
        $response->assertRedirect('/produccion/ordenes-de-produccion');
        $response->assertSessionHas('success', 'Parte diario registrado.');
        $this->assertDatabaseHas('partes_diarios', [
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $corte->id,
            'fecha' => now()->toDateString(),
            'cantidad_conformada' => 5,
        ]);
    }

    public function test_supervisor_can_register_a_parte_with_machine(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        [$orden, $corte] = $this->ordenConHojaDeRuta();
        $maquinaria = Maquinaria::factory()->create([
            'estado_operativo' => EstadoMaquinaria::Disponible->value,
        ]);
        $orden->maquinarias()->attach($maquinaria->id);

        // Act
        $response = $this->actingAs($usuario)
            ->from('/produccion/ordenes-de-produccion')
            ->post("/produccion/ordenes-de-produccion/{$orden->id}/partes", [
                'etapa_id' => $corte->id,
                'maquinaria_id' => $maquinaria->id,
                'fecha' => now()->toDateString(),
                'cantidad' => 3,
                'observaciones' => null,
            ]);

        // Assert
        $response->assertRedirect('/produccion/ordenes-de-produccion');
        $this->assertDatabaseHas('partes_diarios', [
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $corte->id,
            'maquinaria_id' => $maquinaria->id,
            'cantidad_conformada' => 3,
        ]);
    }
}
