<?php

namespace Tests\Feature\Modules\Reportes;

use App\Modules\Reportes\Services\DashboardService;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): DashboardService
    {
        return app(DashboardService::class);
    }

    private function usuarioConRol(Rol $rol): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole($rol->value);

        return $usuario;
    }

    public function test_administrador_receives_every_area_summary(): void
    {
        // Arrange
        $admin = $this->usuarioConRol(Rol::Administrador);

        // Act
        $resumen = $this->service()->resumen($admin);

        // Assert
        $this->assertSame(['produccion', 'inventario', 'compras', 'mantenimiento'], array_keys($resumen));
        $this->assertSame(0, $resumen['produccion']['activas']);
        $this->assertSame(0, $resumen['inventario']['activos']);
        $this->assertSame(0, $resumen['compras']['pendientes_recepcion']);
        $this->assertSame(0, $resumen['mantenimiento']['maquinas_bloqueadas']);
        $this->assertSame([], $resumen['mantenimiento']['bloqueadas']);
    }

    public function test_supervisor_de_produccion_only_receives_production_and_maintenance(): void
    {
        // Arrange
        $supervisor = $this->usuarioConRol(Rol::SupervisorProduccion);

        // Act
        $resumen = $this->service()->resumen($supervisor);

        // Assert
        $this->assertNotNull($resumen['produccion']);
        $this->assertNotNull($resumen['mantenimiento']);
        $this->assertNull($resumen['inventario']);
        $this->assertNull($resumen['compras']);
    }

    public function test_encargado_de_stock_only_receives_inventory(): void
    {
        // Arrange
        $encargado = $this->usuarioConRol(Rol::EncargadoStock);

        // Act
        $resumen = $this->service()->resumen($encargado);

        // Assert
        $this->assertNull($resumen['produccion']);
        $this->assertNotNull($resumen['inventario']);
        $this->assertNull($resumen['compras']);
        $this->assertNull($resumen['mantenimiento']);
    }

    public function test_encargado_de_compras_only_receives_purchases(): void
    {
        // Arrange
        $encargado = $this->usuarioConRol(Rol::EncargadoCompras);

        // Act
        $resumen = $this->service()->resumen($encargado);

        // Assert
        $this->assertNull($resumen['produccion']);
        $this->assertNull($resumen['inventario']);
        $this->assertNotNull($resumen['compras']);
        $this->assertNull($resumen['mantenimiento']);
    }

    public function test_a_usuario_without_area_permissions_receives_no_summaries(): void
    {
        // Arrange
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);
        $usuario = Usuario::factory()->create();
        $usuario->assignRole(Rol::Gerente->value);

        // Act
        $resumen = $this->service()->resumen($usuario);

        // Assert
        $this->assertSame([
            'produccion' => null,
            'inventario' => null,
            'compras' => null,
            'mantenimiento' => null,
        ], $resumen);
    }

    public function test_a_usuario_without_any_role_receives_no_summaries(): void
    {
        // Arrange
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);
        $usuario = Usuario::factory()->create();

        // Act
        $resumen = $this->service()->resumen($usuario);

        // Assert
        $this->assertSame([
            'produccion' => null,
            'inventario' => null,
            'compras' => null,
            'mantenimiento' => null,
        ], $resumen);
    }
}
