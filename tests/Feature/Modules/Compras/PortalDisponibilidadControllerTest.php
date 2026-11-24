<?php

namespace Tests\Feature\Modules\Compras;

use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Models\SolicitudDeDisponibilidad;
use App\Modules\Compras\Services\GestionDisponibilidadService;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PortalDisponibilidadControllerTest extends TestCase
{
    use RefreshDatabase;

    private function solicitud(): SolicitudDeDisponibilidad
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $actor = Usuario::factory()->create();
        $actor->assignRole(Rol::EncargadoCompras->value);

        $articulo = Articulo::factory()->create([
            'descripcion' => 'Tabla de pino',
            'tipo' => 'Materia Prima',
            'estado' => 'Activo',
        ]);

        $proveedor = Proveedor::factory()->create([
            'razon_social' => 'Aceros del Sur',
            'email' => 'ventas@aceros.test',
            'estado' => 'Activo',
        ]);

        Mail::fake();

        return app(GestionDisponibilidadService::class)->crear($actor, $articulo->id, 50, $proveedor->id);
    }

    public function test_the_supplier_portal_renders_the_request(): void
    {
        $solicitud = $this->solicitud();

        $this->get("/portal/disponibilidad/{$solicitud->token}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Disponibilidad')
                ->where('solicitud.articulo', 'Tabla de pino')
                ->where('solicitud.cantidad_solicitada', 50)
            );
    }

    public function test_the_supplier_can_answer(): void
    {
        $solicitud = $this->solicitud();

        $this->post("/portal/disponibilidad/{$solicitud->token}", [
            'cantidad_disponible' => 40,
            'precio_unitario' => 120.5,
            'fecha_prometida' => now()->addDays(10)->toDateString(),
        ])->assertRedirect("/portal/disponibilidad/{$solicitud->token}");

        $this->assertDatabaseHas('cotizaciones', [
            'solicitud_de_disponibilidad_id' => $solicitud->id,
        ]);
        $this->assertNull($solicitud->refresh()->token);
    }

    public function test_a_used_token_returns_not_found(): void
    {
        $solicitud = $this->solicitud();
        $token = (string) $solicitud->token;

        // Consume the token without going through the portal, so the visitor is
        // a fresh session with no confirmation flash.
        app(GestionDisponibilidadService::class)->registrarRespuesta(
            $token,
            40,
            120.5,
            now()->addDays(10)->toDateString(),
        );

        $this->get("/portal/disponibilidad/{$token}")->assertNotFound();
    }

    public function test_a_zero_availability_answer_is_accepted(): void
    {
        $solicitud = $this->solicitud();

        $this->post("/portal/disponibilidad/{$solicitud->token}", [
            'cantidad_disponible' => 0,
            'precio_unitario' => 100,
            'fecha_prometida' => now()->addDays(10)->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('lineas_de_cotizacion', ['cantidad_ofertada' => 0]);
    }
}
