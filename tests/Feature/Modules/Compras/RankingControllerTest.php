<?php

namespace Tests\Feature\Modules\Compras;

use App\Modules\Compras\Models\DesempenoProveedor;
use App\Modules\Compras\Models\OrdenDeCompra;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Factories\SolicitudDeDisponibilidadFactory;
use Database\Seeders\ParametroGlobalSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class RankingControllerTest extends TestCase
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
        $response = $this->get('/compras/ranking');

        // Assert
        $response->assertRedirect('/login');
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->get('/compras/ranking');

        // Assert
        $response->assertForbidden();
    }

    public function test_a_purchasing_manager_can_view_the_ranking(): void
    {
        // Arrange
        $this->seed(ParametroGlobalSeeder::class);
        $usuario = $this->usuarioConRol(Rol::EncargadoCompras);
        $proveedor = Proveedor::factory()->create([
            'razon_social' => 'Aceros del Sur',
            'estado' => 'Activo',
        ]);

        $solicitud = SolicitudDeDisponibilidadFactory::new()->create([
            'proveedor_id' => $proveedor->id,
        ]);

        $orden = OrdenDeCompra::factory()->create([
            'numero_oc' => 'OC-TEST-0001',
            'proveedor_id' => $proveedor->id,
            'solicitud_de_disponibilidad_id' => $solicitud->id,
        ]);

        DesempenoProveedor::create([
            'proveedor_id' => $proveedor->id,
            'orden_de_compra_id' => $orden->id,
            'tiempo_entrega_real' => 3,
            'fecha_registro' => today(),
        ]);

        // Act
        $response = $this->actingAs($usuario)->get('/compras/ranking');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Compras/Ranking/Index')
            ->has('parametros.pesos')
            ->has('parametros.ventana_meses')
            ->has('filas', 1)
            ->where('filas.0.proveedor', 'Aceros del Sur')
            ->has('sinHistorial', 0));
    }
}
