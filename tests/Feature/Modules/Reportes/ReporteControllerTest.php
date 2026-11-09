<?php

namespace Tests\Feature\Modules\Reportes;

use App\Modules\Inventario\Models\Articulo;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ReporteControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<int, string>
     */
    private array $archivosGenerados = [];

    protected function tearDown(): void
    {
        foreach ($this->archivosGenerados as $archivo) {
            if (is_file($archivo)) {
                unlink($archivo);
            }
        }

        parent::tearDown();
    }

    private function usuarioConRol(Rol $rol): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole($rol->value);

        return $usuario;
    }

    private function ordenEnRango(): OrdenDeProduccion
    {
        $articulo = Articulo::factory()->create(['descripcion' => 'Silla de pino']);

        return OrdenDeProduccion::factory()->create([
            'articulo_id' => $articulo->id,
            'estado' => 'Pendiente',
            'fecha_entrega' => '2026-03-10',
        ]);
    }

    public function test_guests_are_redirected_to_the_login_screen(): void
    {
        // Act
        $response = $this->get('/reportes');

        // Assert
        $response->assertRedirect('/login');
    }

    public function test_users_without_report_permissions_are_forbidden(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();

        // Act / Assert
        $this->actingAs($usuario)->get('/reportes')->assertForbidden();
        $this->actingAs($usuario)
            ->get('/reportes/exportar?tipo=produccion&desde=2026-03-01&hasta=2026-03-31&formato=pdf')
            ->assertForbidden();
    }

    public function test_an_administrador_sees_every_available_report_type_on_the_index(): void
    {
        // Arrange
        $admin = $this->usuarioConRol(Rol::Administrador);

        // Act
        $response = $this->actingAs($admin)->get('/reportes');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Reportes/Index')
            ->where('tipos', [
                'produccion' => 'Producción',
                'inventario' => 'Inventario',
                'compras' => 'Compras',
            ])
            ->where('preview', null)
            ->where('filtros', [])
        );
    }

    public function test_the_index_only_lists_the_types_the_actor_can_consult(): void
    {
        // Arrange
        $supervisor = $this->usuarioConRol(Rol::SupervisorProduccion);

        // Act
        $response = $this->actingAs($supervisor)->get('/reportes');

        // Assert
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Reportes/Index')
            ->where('tipos', ['produccion' => 'Producción'])
        );
    }

    public function test_the_index_builds_a_preview_when_filters_are_present(): void
    {
        // Arrange
        $admin = $this->usuarioConRol(Rol::Administrador);
        $orden = $this->ordenEnRango();

        // Act
        $response = $this->actingAs($admin)->get('/reportes?tipo=produccion&desde=2026-03-01&hasta=2026-03-31');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Reportes/Index')
            ->where('preview.tipo', 'produccion')
            ->where('preview.total', 1)
            ->where('preview.filas.0.orden_id', $orden->id)
        );
    }

    public function test_the_index_forbids_a_type_the_actor_cannot_consult(): void
    {
        // Arrange
        $supervisor = $this->usuarioConRol(Rol::SupervisorProduccion);

        // Act
        $response = $this->actingAs($supervisor)
            ->get('/reportes?tipo=compras&desde=2026-03-01&hasta=2026-03-31');

        // Assert
        $response->assertForbidden();
    }

    public function test_the_index_rejects_an_inverted_date_range(): void
    {
        // Arrange
        $admin = $this->usuarioConRol(Rol::Administrador);

        // Act
        $response = $this->actingAs($admin)
            ->get('/reportes?tipo=produccion&desde=2026-03-31&hasta=2026-03-01');

        // Assert
        $response->assertSessionHasErrors('hasta');
    }

    public function test_exporting_a_pdf_returns_an_application_pdf_download(): void
    {
        // Arrange
        $admin = $this->usuarioConRol(Rol::Administrador);
        $this->ordenEnRango();

        // Act
        $response = $this->actingAs($admin)
            ->get('/reportes/exportar?tipo=produccion&desde=2026-03-01&hasta=2026-03-31&formato=pdf');

        // Assert
        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());
    }

    public function test_exporting_an_xlsx_returns_a_spreadsheet_download(): void
    {
        // Arrange
        $admin = $this->usuarioConRol(Rol::Administrador);
        $this->ordenEnRango();

        $directorio = storage_path('app/reportes');
        $antes = is_dir($directorio) ? glob($directorio.'/*.xlsx') : [];

        // Act
        $response = $this->actingAs($admin)
            ->get('/reportes/exportar?tipo=produccion&desde=2026-03-01&hasta=2026-03-31&formato=xlsx');

        $despues = is_dir($directorio) ? glob($directorio.'/*.xlsx') : [];
        $this->archivosGenerados = array_values(array_diff($despues, $antes));

        // Assert
        $response->assertOk();
        $response->assertDownload();
    }

    public function test_exporting_rejects_an_unsupported_format(): void
    {
        // Arrange
        $admin = $this->usuarioConRol(Rol::Administrador);

        // Act
        $response = $this->actingAs($admin)
            ->get('/reportes/exportar?tipo=produccion&desde=2026-03-01&hasta=2026-03-31&formato=csv');

        // Assert
        $response->assertSessionHasErrors('formato');
    }

    public function test_exporting_forbids_a_type_the_actor_cannot_consult(): void
    {
        // Arrange
        $supervisor = $this->usuarioConRol(Rol::SupervisorProduccion);

        // Act
        $response = $this->actingAs($supervisor)
            ->get('/reportes/exportar?tipo=compras&desde=2026-03-01&hasta=2026-03-31&formato=pdf');

        // Assert
        $response->assertForbidden();
    }
}
