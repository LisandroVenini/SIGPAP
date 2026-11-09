<?php

namespace Tests\Feature\Modules\Reportes;

use App\Modules\Inventario\Models\Articulo;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Reportes\Services\GestionReportesService;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GestionReportesServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): GestionReportesService
    {
        return app(GestionReportesService::class);
    }

    private function usuarioConRol(Rol $rol): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole($rol->value);

        return $usuario;
    }

    public function test_tipos_disponibles_returns_only_the_reports_allowed_for_the_actor(): void
    {
        // Arrange
        $produccion = $this->usuarioConRol(Rol::SupervisorProduccion);
        $inventario = $this->usuarioConRol(Rol::EncargadoStock);
        $compras = $this->usuarioConRol(Rol::EncargadoCompras);

        // Act / Assert
        $this->assertSame(['produccion' => 'Producción'], $this->service()->tiposDisponibles($produccion));
        $this->assertSame(['inventario' => 'Inventario'], $this->service()->tiposDisponibles($inventario));
        $this->assertSame(['compras' => 'Compras'], $this->service()->tiposDisponibles($compras));
    }

    public function test_an_administrador_can_see_every_report_type(): void
    {
        // Arrange
        $admin = $this->usuarioConRol(Rol::Administrador);

        // Act
        $tipos = $this->service()->tiposDisponibles($admin);

        // Assert
        $this->assertSame(['produccion', 'inventario', 'compras'], array_keys($tipos));
    }

    public function test_a_gerente_without_report_permissions_sees_no_report_types(): void
    {
        // Arrange
        $gerente = $this->usuarioConRol(Rol::Gerente);

        // Act / Assert
        $this->assertSame([], $this->service()->tiposDisponibles($gerente));
    }

    public function test_generar_rejects_a_type_that_is_not_available_for_the_actor(): void
    {
        // Arrange
        $actor = $this->usuarioConRol(Rol::SupervisorProduccion);

        // Act / Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('El tipo de reporte no está disponible para el usuario.');

        $this->service()->generar($actor, 'compras', [
            'desde' => '2026-05-01',
            'hasta' => '2026-05-31',
        ]);
    }

    public function test_generar_returns_the_production_report_shape(): void
    {
        // Arrange
        $actor = $this->usuarioConRol(Rol::SupervisorProduccion);
        $articulo = Articulo::factory()->create(['descripcion' => 'Silla de pino']);
        $orden = OrdenDeProduccion::factory()->create([
            'articulo_id' => $articulo->id,
            'estado' => 'Pendiente',
            'cantidad_requerida' => 10,
            'cantidad_producida' => 0,
            'fecha_entrega' => '2026-03-10',
        ]);
        $orden->planificacionDeProduccion->update(['nombre' => 'Plan marzo']);

        // Act
        $resultado = $this->service()->generar($actor, 'produccion', [
            'desde' => '2026-03-01',
            'hasta' => '2026-03-31',
            'estado' => 'Pendiente',
        ]);

        // Assert
        $this->assertSame('produccion', $resultado['tipo']);
        $this->assertSame('Producción', $resultado['titulo']);
        $this->assertSame([
            'Orden',
            'Producto',
            'Planificación',
            'Estado',
            'Cantidad requerida',
            'Cantidad producida',
            'Fecha de entrega',
            'Fecha de inicio',
            'Fecha de fin',
        ], $resultado['columnas']);
        $this->assertSame(1, $resultado['total']);
        $this->assertSame([
            'orden_id' => $orden->id,
            'producto' => 'Silla de pino',
            'planificacion' => 'Plan marzo',
            'estado' => 'Pendiente',
            'cantidad_requerida' => 10.0,
            'cantidad_producida' => 0.0,
            'fecha_entrega' => '2026-03-10',
            'fecha_inicio' => null,
            'fecha_fin' => null,
        ], $resultado['filas'][0]);
    }

    public function test_puede_exportar_matches_the_available_types(): void
    {
        // Arrange
        $actor = $this->usuarioConRol(Rol::SupervisorProduccion);

        // Act / Assert
        $this->assertTrue($this->service()->puedeExportar($actor, 'produccion'));
        $this->assertFalse($this->service()->puedeExportar($actor, 'inventario'));
        $this->assertFalse($this->service()->puedeExportar($actor, 'compras'));
    }
}
