<?php

namespace Tests\Feature\Modules\Compras;

use App\Modules\Compras\Mail\SolicitudDeDisponibilidadMail;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Services\GestionDisponibilidadService;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\ParametroGlobalSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReglaDeAlertaSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DisponibilidadControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            PermissionSeeder::class,
            ReglaDeAlertaSeeder::class,
            ParametroGlobalSeeder::class,
        ]);
    }

    private function usuarioConRol(Rol $rol): Usuario
    {
        $usuario = Usuario::factory()->create();
        $usuario->assignRole($rol->value);

        return $usuario;
    }

    private function articulo(): Articulo
    {
        return Articulo::factory()->create([
            'descripcion' => 'Tabla de pino',
            'tipo' => 'Materia Prima',
            'estado' => 'Activo',
            'stock_fisico' => 5,
            'stock_minimo' => 20,
        ]);
    }

    private function proveedor(): Proveedor
    {
        return Proveedor::factory()->create([
            'razon_social' => 'Aceros del Sur',
            'email' => 'ventas@aceros.test',
            'estado' => 'Activo',
        ]);
    }

    public function test_an_encargado_sees_the_page(): void
    {
        $this->articulo();
        $this->proveedor();

        $this->actingAs($this->usuarioConRol(Rol::EncargadoCompras))
            ->get('/compras/disponibilidad')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Compras/Disponibilidad/Index')
                ->has('bajos', 1)
                ->has('proveedores')
                ->has('solicitudes')
            );
    }

    public function test_a_user_without_the_permission_gets_forbidden(): void
    {
        $this->actingAs($this->usuarioConRol(Rol::SupervisorProduccion))
            ->get('/compras/disponibilidad')
            ->assertForbidden();
    }

    public function test_it_creates_the_availability_request(): void
    {
        Mail::fake();
        $articulo = $this->articulo();
        $proveedor = $this->proveedor();

        $this->actingAs($this->usuarioConRol(Rol::EncargadoCompras))
            ->post('/compras/disponibilidad', [
                'articulo_id' => $articulo->id,
                'cantidad' => 50,
                'proveedor_id' => $proveedor->id,
                'fecha_requerida' => now()->addDays(10)->toDateString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('solicitudes_de_disponibilidad', [
            'articulo_id' => $articulo->id,
            'estado' => 'Enviada',
        ]);
        Mail::assertSent(SolicitudDeDisponibilidadMail::class);
    }

    public function test_it_shows_the_recommendation_of_an_answered_request(): void
    {
        Mail::fake();
        $actor = $this->usuarioConRol(Rol::EncargadoCompras);
        $articulo = $this->articulo();
        $proveedor = $this->proveedor();

        $servicio = app(GestionDisponibilidadService::class);
        $solicitud = $servicio->crear(
            $actor,
            $articulo->id,
            50,
            $proveedor->id,
            now()->addDays(10)->toDateString(),
        );

        $servicio->registrarRespuesta(
            (string) $solicitud->token,
            40,
            100,
            now()->addDays(5)->toDateString(),
        );
        $servicio->resolver($solicitud, $actor->id);

        $this->actingAs($actor)
            ->get('/compras/disponibilidad')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('solicitudes', 1)
                ->where('solicitudes.0.estado', 'Respondida')
                ->where('solicitudes.0.recomendacion.escenario', 'Ideal')
            );
    }
}
