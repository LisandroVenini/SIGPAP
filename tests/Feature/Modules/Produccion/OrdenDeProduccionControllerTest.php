<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Inventario\Enums\EstadoArticulo;
use App\Modules\Inventario\Enums\OrigenArticulo;
use App\Modules\Inventario\Enums\TipoArticulo;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Produccion\Enums\EstadoMaquinaria;
use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use App\Modules\Produccion\Models\AsignacionDeEtapa;
use App\Modules\Produccion\Models\ComponenteDeReceta;
use App\Modules\Produccion\Models\ControlDeCalidad;
use App\Modules\Produccion\Models\Etapa;
use App\Modules\Produccion\Models\Maquinaria;
use App\Modules\Produccion\Models\Operario;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Models\ParteDiario;
use App\Modules\Produccion\Models\PlanificacionDeProduccion;
use App\Modules\Produccion\Models\Receta;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class OrdenDeProduccionControllerTest extends TestCase
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

    public function test_guests_are_redirected_to_the_login_screen(): void
    {
        // Act
        $response = $this->get('/produccion/ordenes-de-produccion');

        // Assert
        $response->assertRedirect('/login');
    }

    public function test_usuarios_without_role_are_forbidden(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->get('/produccion/ordenes-de-produccion');

        // Assert
        $response->assertForbidden();
    }

    public function test_searching_an_unknown_product_returns_no_orders(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        OrdenDeProduccion::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->get('/produccion/ordenes-de-produccion?buscar=SKU-INEXISTENTE');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Produccion/OrdenesDeProduccion/Index')
            ->has('ordenes.data', 0)
            ->where('ordenes.total', 0));
    }

    public function test_a_supervisor_can_list_ordenes(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        OrdenDeProduccion::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->get('/produccion/ordenes-de-produccion');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Produccion/OrdenesDeProduccion/Index')
            ->has('ordenes.data')
            ->has('filtros'));
    }

    public function test_a_supervisor_can_create_orders_in_batch(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $planificacion = PlanificacionDeProduccion::factory()->create();
        $insumo = $this->insumo(['stock_fisico' => 100]);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 10);

        // Act
        $response = $this->actingAs($usuario)->post('/produccion/ordenes-de-produccion', [
            'planificacion_de_produccion_id' => $planificacion->id,
            'fecha_entrega' => '2026-11-15',
            'modo' => 'todas',
            'lineas' => [
                ['articulo_id' => $producto->id, 'cantidad' => 5],
            ],
        ]);

        // Assert
        $response->assertRedirect('/produccion/ordenes-de-produccion');
        $response->assertSessionHas('success');
        $this->assertDatabaseCount('ordenes_de_produccion', 1);
    }

    public function test_solo_disponibles_does_not_create_uncovered_orders(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $planificacion = PlanificacionDeProduccion::factory()->create();
        $insumo = $this->insumo(['stock_fisico' => 0]);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 10);

        // Act
        $response = $this->actingAs($usuario)->post('/produccion/ordenes-de-produccion', [
            'planificacion_de_produccion_id' => $planificacion->id,
            'fecha_entrega' => '2026-11-15',
            'modo' => 'solo_disponibles',
            'lineas' => [
                ['articulo_id' => $producto->id, 'cantidad' => 5],
            ],
        ]);

        // Assert
        $response->assertRedirect('/produccion/ordenes-de-produccion');
        $this->assertDatabaseCount('ordenes_de_produccion', 0);
    }

    public function test_an_invalid_state_is_rejected_with_session_error(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $planificacion = PlanificacionDeProduccion::factory()->create();

        // Act
        $response = $this->actingAs($usuario)
            ->from('/produccion/ordenes-de-produccion')
            ->get('/produccion/ordenes-de-produccion?estado=Invalido');

        // Assert
        $response->assertRedirect('/produccion/ordenes-de-produccion');
        $response->assertSessionHasErrors('estado');
    }

    public function test_a_supervisor_can_update_an_order(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $insumo = $this->insumo(['stock_fisico' => 100]);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 1);
        $orden = OrdenDeProduccion::factory()->create([
            'articulo_id' => $producto->id,
            'estado' => EstadoOrdenProduccion::Pendiente->value,
        ]);

        // Act
        $response = $this->actingAs($usuario)->put("/produccion/ordenes-de-produccion/{$orden->id}", [
            'cantidad' => 25,
            'fecha_entrega' => '2026-11-25',
        ]);

        // Assert
        $response->assertRedirect('/produccion/ordenes-de-produccion');
        $this->assertDatabaseHas('ordenes_de_produccion', [
            'id' => $orden->id,
            'cantidad_requerida' => 25,
            'fecha_entrega' => '2026-11-25',
        ]);
    }

    public function test_a_supervisor_can_anular_an_order(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $orden = OrdenDeProduccion::factory()->create([
            'estado' => EstadoOrdenProduccion::Pendiente->value,
        ]);

        // Act
        $response = $this->actingAs($usuario)->post("/produccion/ordenes-de-produccion/{$orden->id}/anular", [
            'motivo' => 'Sin uso',
        ]);

        // Assert
        $response->assertRedirect('/produccion/ordenes-de-produccion');
        $this->assertDatabaseHas('ordenes_de_produccion', [
            'id' => $orden->id,
            'estado' => EstadoOrdenProduccion::Anulada->value,
        ]);
    }

    public function test_a_supervisor_can_assign_resources_to_an_order(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $orden = OrdenDeProduccion::factory()->create([
            'estado' => EstadoOrdenProduccion::Pendiente->value,
        ]);
        $operario = Operario::factory()->create();
        $maquinaria = Maquinaria::factory()->create([
            'estado_operativo' => EstadoMaquinaria::Disponible->value,
        ]);

        // Act
        $response = $this->actingAs($usuario)
            ->from('/produccion/ordenes-de-produccion')
            ->put("/produccion/ordenes-de-produccion/{$orden->id}/recursos", [
                'operarios' => [$operario->id],
                'maquinarias' => [$maquinaria->id],
            ]);

        // Assert
        $response->assertRedirect('/produccion/ordenes-de-produccion');
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('orden_de_produccion_operario', [
            'orden_de_produccion_id' => $orden->id,
            'operario_id' => $operario->id,
        ]);
        $this->assertDatabaseHas('orden_de_produccion_maquinaria', [
            'orden_de_produccion_id' => $orden->id,
            'maquinaria_id' => $maquinaria->id,
        ]);
    }

    public function test_a_supervisor_can_start_an_order(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $orden = OrdenDeProduccion::factory()->create([
            'estado' => EstadoOrdenProduccion::Asignada->value,
        ]);
        $operario = Operario::factory()->create();
        $maquinaria = Maquinaria::factory()->create();
        $orden->operarios()->attach($operario);
        $orden->maquinarias()->attach($maquinaria);

        // Act
        $response = $this->actingAs($usuario)
            ->from('/produccion/ordenes-de-produccion')
            ->post("/produccion/ordenes-de-produccion/{$orden->id}/iniciar");

        // Assert
        $response->assertRedirect('/produccion/ordenes-de-produccion');
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('ordenes_de_produccion', [
            'id' => $orden->id,
            'estado' => EstadoOrdenProduccion::EnCurso->value,
        ]);
    }

    public function test_assigning_resources_in_invalid_state_returns_session_error(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $orden = OrdenDeProduccion::factory()->create([
            'estado' => EstadoOrdenProduccion::EnCurso->value,
        ]);

        // Act
        $response = $this->actingAs($usuario)
            ->from('/produccion/ordenes-de-produccion')
            ->put("/produccion/ordenes-de-produccion/{$orden->id}/recursos", [
                'operarios' => [],
                'maquinarias' => [],
            ]);

        // Assert
        $response->assertRedirect('/produccion/ordenes-de-produccion');
        $response->assertSessionHasErrors('estado');
    }

    public function test_a_supervisor_can_register_quality_control_by_part(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $insumo = $this->insumo(['stock_fisico' => 100]);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 2);
        $corte = Etapa::factory()->create();
        $ensamble = Etapa::factory()->create();
        AsignacionDeEtapa::factory()->create(['articulo_id' => $producto->id, 'etapa_id' => $corte->id, 'orden' => 1]);
        AsignacionDeEtapa::factory()->create(['articulo_id' => $producto->id, 'etapa_id' => $ensamble->id, 'orden' => 2]);
        $orden = OrdenDeProduccion::factory()->create([
            'articulo_id' => $producto->id,
            'estado' => EstadoOrdenProduccion::EnCurso->value,
        ]);
        $parte = ParteDiario::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $corte->id,
            'cantidad_conformada' => 8,
        ]);

        // Act
        $response = $this->actingAs($usuario)
            ->from('/produccion/ordenes-de-produccion')
            ->post("/produccion/ordenes-de-produccion/{$orden->id}/calidad", [
                'parte_diario_id' => $parte->id,
                'cantidad_rechazada' => 2,
                'motivo' => 'Defecto menor',
            ]);

        // Assert
        $response->assertRedirect('/produccion/ordenes-de-produccion');
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('controles_de_calidad', [
            'orden_de_produccion_id' => $orden->id,
            'parte_diario_id' => $parte->id,
            'cantidad_aprobada' => 6,
            'cantidad_rechazada' => 2,
        ]);
    }

    public function test_a_supervisor_can_close_an_order(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $producto = $this->productoFabricado();
        $ensamble = Etapa::factory()->create();
        AsignacionDeEtapa::factory()->create(['articulo_id' => $producto->id, 'etapa_id' => $ensamble->id, 'orden' => 1]);
        $orden = OrdenDeProduccion::factory()->create([
            'articulo_id' => $producto->id,
            'estado' => EstadoOrdenProduccion::EnCurso->value,
            'cantidad_requerida' => 10,
        ]);
        $parte = ParteDiario::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $ensamble->id,
            'cantidad_conformada' => 10,
        ]);
        ControlDeCalidad::factory()->create([
            'orden_de_produccion_id' => $orden->id,
            'parte_diario_id' => $parte->id,
            'cantidad_aprobada' => 10,
            'cantidad_rechazada' => 0,
        ]);

        // Act
        $response = $this->actingAs($usuario)
            ->from('/produccion/ordenes-de-produccion')
            ->post("/produccion/ordenes-de-produccion/{$orden->id}/cerrar", [
                'unidades_finales' => 10,
            ]);

        // Assert
        $response->assertRedirect('/produccion/ordenes-de-produccion');
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('ordenes_de_produccion', [
            'id' => $orden->id,
            'estado' => EstadoOrdenProduccion::Cerrada->value,
            'cantidad_producida' => 10,
        ]);
    }

    public function test_a_supervisor_can_postpone_an_order(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $orden = OrdenDeProduccion::factory()->create([
            'estado' => EstadoOrdenProduccion::EnCurso->value,
        ]);

        // Act
        $response = $this->actingAs($usuario)
            ->from('/produccion/ordenes-de-produccion')
            ->post("/produccion/ordenes-de-produccion/{$orden->id}/posponer", [
                'fecha_prevista_inicio' => '2026-12-01',
                'liberar_reservas' => true,
                'motivo' => 'Falta personal',
            ]);

        // Assert
        $response->assertRedirect('/produccion/ordenes-de-produccion');
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('ordenes_de_produccion', [
            'id' => $orden->id,
            'estado' => EstadoOrdenProduccion::Pospuesta->value,
            'fecha_prevista_inicio' => '2026-12-01',
        ]);
    }

    public function test_a_supervisor_can_resume_an_order(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $insumo = $this->insumo(['stock_fisico' => 100]);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 2);
        $corte = Etapa::factory()->create();
        AsignacionDeEtapa::factory()->create(['articulo_id' => $producto->id, 'etapa_id' => $corte->id, 'orden' => 1]);
        $orden = OrdenDeProduccion::factory()->create([
            'articulo_id' => $producto->id,
            'estado' => EstadoOrdenProduccion::Pospuesta->value,
            'cantidad_requerida' => 10,
            'fecha_inicio' => '2026-11-01',
        ]);

        // Act
        $response = $this->actingAs($usuario)
            ->from('/produccion/ordenes-de-produccion')
            ->post("/produccion/ordenes-de-produccion/{$orden->id}/reanudar");

        // Assert
        $response->assertRedirect('/produccion/ordenes-de-produccion');
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('ordenes_de_produccion', [
            'id' => $orden->id,
            'estado' => EstadoOrdenProduccion::EnCurso->value,
        ]);
    }

    public function test_resume_without_stock_returns_insumos_error(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::SupervisorProduccion);
        $insumo = $this->insumo(['stock_fisico' => 0]);
        $producto = $this->productoFabricado();
        $this->recetaConComponente($producto, $insumo, 2);
        $corte = Etapa::factory()->create();
        AsignacionDeEtapa::factory()->create(['articulo_id' => $producto->id, 'etapa_id' => $corte->id, 'orden' => 1]);
        $orden = OrdenDeProduccion::factory()->create([
            'articulo_id' => $producto->id,
            'estado' => EstadoOrdenProduccion::Pospuesta->value,
            'cantidad_requerida' => 10,
            'fecha_inicio' => '2026-11-01',
        ]);

        // Act
        $response = $this->actingAs($usuario)
            ->from('/produccion/ordenes-de-produccion')
            ->post("/produccion/ordenes-de-produccion/{$orden->id}/reanudar");

        // Assert
        $response->assertRedirect('/produccion/ordenes-de-produccion');
        $response->assertSessionHasErrors('insumos');
        $this->assertDatabaseHas('ordenes_de_produccion', [
            'id' => $orden->id,
            'estado' => EstadoOrdenProduccion::PendientePorInsumos->value,
        ]);
    }
}
