<?php

namespace Tests\Feature\Modules\Predictivo;

use App\Modules\Inventario\Models\Articulo;
use App\Modules\Produccion\Models\AsignacionDeEtapa;
use App\Modules\Produccion\Models\Etapa;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use App\Shared\Alertas\Models\Alerta;
use Database\Seeders\ParametroGlobalSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TableroPredictivoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    }

    private function usuarioConRol(Rol $rol): Usuario
    {
        $usuario = Usuario::factory()->create();
        $usuario->assignRole($rol->value);

        return $usuario;
    }

    private function orden(array $atributos = []): OrdenDeProduccion
    {
        $producto = Articulo::factory()->create();

        return OrdenDeProduccion::factory()->create($atributos + [
            'articulo_id' => $producto->id,
            'estado' => 'Pendiente',
            'cantidad_requerida' => 10,
            'fecha_entrega' => now()->addDays(20)->toDateString(),
        ]);
    }

    public function test_a_gerente_sees_the_board(): void
    {
        $this->orden();

        $this->actingAs($this->usuarioConRol(Rol::Gerente))
            ->get('/predictivo/tablero')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Predictivo/Tablero/Index')
                ->has('cronograma.filas', 1)
                ->has('kpis.ordenes_activas')
                ->has('alertas')
                ->where('sinDatos', false)
                ->where('calendarioConfigurado', false)
            );
    }

    public function test_a_user_without_the_permission_gets_forbidden(): void
    {
        $this->actingAs($this->usuarioConRol(Rol::SupervisorProduccion))
            ->get('/predictivo/tablero')
            ->assertForbidden();
    }

    public function test_the_empty_state_is_flagged_when_there_are_no_orders(): void
    {
        $this->actingAs($this->usuarioConRol(Rol::Gerente))
            ->get('/predictivo/tablero')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('sinDatos', true)
                ->has('cronograma.filas', 0)
            );
    }

    public function test_it_lists_the_open_bottleneck_alerts(): void
    {
        $orden = $this->orden();

        $alerta = Alerta::query()->create([
            'tipo' => 'cuello_de_botella_predictivo',
            'severidad' => 'Atencion',
            'origen_tipo' => 'condicion',
            'fecha' => now(),
            'descripcion' => 'La orden se proyecta con atraso.',
            'orden_de_produccion_id' => $orden->id,
        ]);
        $alerta->roles()->sync([Role::findByName(Rol::Gerente->value, 'web')->id]);

        $this->actingAs($this->usuarioConRol(Rol::Gerente))
            ->get('/predictivo/tablero')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('alertas', 1)
                ->where('alertas.0.descripcion', 'La orden se proyecta con atraso.')
            );
    }

    public function test_the_risk_filter_keeps_only_conflicting_orders(): void
    {
        $producto = Articulo::factory()->create();
        $etapa = Etapa::factory()->create();
        AsignacionDeEtapa::factory()->create([
            'articulo_id' => $producto->id,
            'etapa_id' => $etapa->id,
            'tiempo_por_unidad' => 60,
            'orden' => 1,
        ]);

        $this->orden([
            'articulo_id' => $producto->id,
            'fecha_entrega' => now()->subDays(5)->toDateString(),
        ]);
        $this->orden([
            'articulo_id' => $producto->id,
            'fecha_entrega' => now()->addDays(20)->toDateString(),
        ]);

        $this->actingAs($this->usuarioConRol(Rol::Gerente))
            ->get('/predictivo/tablero?solo_riesgo=1')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('cronograma.filas', 1)
                ->where('kpis.ordenes_activas', 1)
                ->where('kpis.en_riesgo', 1)
            );
    }

    public function test_the_board_reports_the_configured_calendar(): void
    {
        $this->seed(ParametroGlobalSeeder::class);

        $this->actingAs($this->usuarioConRol(Rol::Gerente))
            ->get('/predictivo/tablero')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('calendarioConfigurado', true));
    }
}
