<?php

namespace Tests\Feature\Modules\Predictivo;

use App\Modules\Inventario\Enums\EstadoArticulo;
use App\Modules\Inventario\Enums\OrigenArticulo;
use App\Modules\Inventario\Enums\TipoArticulo;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Predictivo\Models\EscenarioDeSimulacion;
use App\Modules\Produccion\Models\AsignacionDeEtapa;
use App\Modules\Produccion\Models\Etapa;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Carbon\CarbonImmutable;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SimuladorControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);
        Carbon::setTestNow(CarbonImmutable::parse('2026-11-02 08:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function usuarioConRol(Rol $rol): Usuario
    {
        $usuario = Usuario::factory()->create();
        $usuario->assignRole($rol->value);

        return $usuario;
    }

    private function productoConRuta(): Articulo
    {
        $producto = Articulo::factory()->create([
            'descripcion' => 'Mesa de pino',
            'tipo' => TipoArticulo::ProductoTerminado->value,
            'origen' => OrigenArticulo::Fabricado->value,
            'estado' => EstadoArticulo::Activo->value,
        ]);

        $etapa = Etapa::factory()->create();
        AsignacionDeEtapa::factory()->create([
            'articulo_id' => $producto->id,
            'etapa_id' => $etapa->id,
            'tiempo_por_unidad' => 480,
            'orden' => 1,
        ]);

        return $producto;
    }

    public function test_a_gerente_sees_the_simulator(): void
    {
        $this->actingAs($this->usuarioConRol(Rol::Gerente))
            ->get('/predictivo/simulador')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Predictivo/Simulador/Index')
                ->has('productos')
                ->where('resultado', null)
            );
    }

    public function test_a_user_without_the_permission_gets_forbidden(): void
    {
        $this->actingAs($this->usuarioConRol(Rol::SupervisorProduccion))
            ->get('/predictivo/simulador')
            ->assertForbidden();
    }

    public function test_it_simulates_and_persists_the_scenario(): void
    {
        $producto = $this->productoConRuta();
        OrdenDeProduccion::factory()->create([
            'articulo_id' => $producto->id,
            'estado' => 'Pendiente',
            'cantidad_requerida' => 1,
            'fecha_entrega' => now()->addDays(30)->toDateString(),
        ]);

        $this->actingAs($this->usuarioConRol(Rol::Gerente))
            ->post('/predictivo/simulador', [
                'articulo_id' => $producto->id,
                'cantidad' => 1,
                'fecha_limite' => '2026-11-05',
            ])
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Predictivo/Simulador/Index')
                ->where('resultado.viable', true)
                ->where('resultado.impacto_proyectado', 1)
                ->has('resultado.retrasos', 1)
            );

        $this->assertSame(1, EscenarioDeSimulacion::query()->count());
    }

    public function test_it_rejects_an_article_that_is_not_a_manufactured_finished_product(): void
    {
        $insumo = Articulo::factory()->create(['tipo' => TipoArticulo::MateriaPrima->value]);

        $this->actingAs($this->usuarioConRol(Rol::Gerente))
            ->from('/predictivo/simulador')
            ->post('/predictivo/simulador', [
                'articulo_id' => $insumo->id,
                'cantidad' => 1,
                'fecha_limite' => '2026-11-05',
            ])
            ->assertRedirect('/predictivo/simulador')
            ->assertSessionHasErrors('articulo_id');
    }
}
