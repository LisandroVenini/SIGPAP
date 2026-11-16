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
use App\Modules\Produccion\Models\ParteDiario;
use App\Modules\Produccion\Models\Receta;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
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
            ->from('/produccion/partes-diarios')
            ->post("/produccion/ordenes-de-produccion/{$orden->id}/partes", [
                'etapa_id' => $corte->id,
                'maquinaria_id' => null,
                'fecha' => now()->toDateString(),
                'cantidad' => 5,
                'observaciones' => 'Inicio de corte',
            ]);

        // Assert
        $response->assertRedirect("/produccion/partes-diarios?orden={$orden->id}");
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
            ->from('/produccion/partes-diarios')
            ->post("/produccion/ordenes-de-produccion/{$orden->id}/partes", [
                'etapa_id' => $corte->id,
                'maquinaria_id' => $maquinaria->id,
                'fecha' => now()->toDateString(),
                'cantidad' => 3,
                'observaciones' => null,
            ]);

        // Assert
        $response->assertRedirect("/produccion/partes-diarios?orden={$orden->id}");
        $this->assertDatabaseHas('partes_diarios', [
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $corte->id,
            'maquinaria_id' => $maquinaria->id,
            'cantidad_conformada' => 3,
        ]);
    }

    public function test_guests_are_redirected_to_login_from_the_daily_part_section(): void
    {
        // Act
        $response = $this->get('/produccion/partes-diarios');

        // Assert
        $response->assertRedirect('/login');
    }

    public function test_users_without_permission_cannot_open_the_daily_part_section(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->get('/produccion/partes-diarios');

        // Assert
        $response->assertForbidden();
    }

    public function test_supervisor_sees_in_progress_orders_with_stage_progress_and_todays_partes(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        [$orden, $corte] = $this->ordenConHojaDeRuta();
        OrdenDeProduccion::factory()->create(['estado' => EstadoOrdenProduccion::Pendiente->value]);
        ParteDiario::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $corte->id,
            'maquinaria_id' => null,
            'fecha' => now()->toDateString(),
            'cantidad_conformada' => 4,
        ]);

        // Act
        $response = $this->actingAs($usuario)->get("/produccion/partes-diarios?orden={$orden->id}");

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Produccion/ParteDiario/Index')
            ->where('ordenSeleccionadaId', $orden->id)
            ->where('fechaHoy', now()->toDateString())
            ->has('ordenes', 1)
            ->where('ordenes.0.id', $orden->id)
            ->where('ordenes.0.cantidad_requerida', 10)
            ->has('ordenes.0.etapas', 1)
            ->where('ordenes.0.etapas.0.etapa_id', $corte->id)
            ->where('ordenes.0.etapas.0.producida', 4)
            ->where('ordenes.0.etapas.0.rechazadas', 0)
            ->where('ordenes.0.etapas.0.faltante', 6)
            ->has('ordenes.0.maquinarias')
            ->has('ordenes.0.partes_hoy', 1)
            ->where('ordenes.0.partes_hoy.0.cantidad', 4));
    }

    public function test_a_parte_before_the_order_start_is_rejected(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        [$orden, $corte] = $this->ordenConHojaDeRuta();
        $orden->fecha_inicio = now()->subDays(2)->toDateString();
        $orden->save();

        // Act
        $response = $this->actingAs($usuario)->post("/produccion/ordenes-de-produccion/{$orden->id}/partes", [
            'etapa_id' => $corte->id,
            'fecha' => now()->subDays(5)->toDateString(),
            'cantidad' => 1,
        ]);

        // Assert
        $response->assertSessionHasErrors('fecha');
        $this->assertDatabaseCount('partes_diarios', 0);
    }
}
