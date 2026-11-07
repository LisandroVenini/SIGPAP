<?php

namespace Tests\Feature\Modules\Compras;

use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Models\SolicitudDeCotizacion;
use App\Modules\Compras\Services\GestionSolicitudesDeCotizacionService;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Factories\SolicitudDeCotizacionFactory;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PortalCotizacionControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole(Rol::EncargadoCompras->value);

        return $usuario;
    }

    /**
     * @return array{solicitud: SolicitudDeCotizacion, proveedor: Proveedor, articulo: Articulo, token: string}
     */
    private function solicitudConToken(string $fechaVencimiento): array
    {
        Mail::fake();

        $articulo = Articulo::factory()->create([
            'descripcion' => 'Tabla de pino',
            'tipo' => 'Materia Prima',
            'estado' => 'Activo',
        ]);
        $proveedor = Proveedor::factory()->create([
            'razon_social' => 'Aceros del Sur',
            'email' => 'ventas@aceros.test',
        ]);

        $solicitud = app(GestionSolicitudesDeCotizacionService::class)->crear($this->actor(), [
            'fecha_vencimiento' => $fechaVencimiento,
            'lineas' => [
                ['articulo_id' => $articulo->id, 'cantidad_solicitada' => 10],
            ],
            'proveedores' => [$proveedor->id],
        ]);

        $token = (string) $solicitud->proveedores()->first()->pivot->token;

        return [
            'solicitud' => $solicitud,
            'proveedor' => $proveedor,
            'articulo' => $articulo,
            'token' => $token,
        ];
    }

    public function test_a_valid_token_opens_the_quote_form(): void
    {
        // Arrange
        $contexto = $this->solicitudConToken(today()->addWeek()->toDateString());

        // Act
        $response = $this->get("/portal/cotizaciones/{$contexto['token']}");

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Portal/Cotizacion')
            ->where('solicitud.solicitud_id', $contexto['solicitud']->id)
            ->where('solicitud.proveedor', 'Aceros del Sur')
            ->where('solicitud.fecha_vencimiento', today()->addWeek()->toDateString())
            ->has('solicitud.lineas', 1)
            ->where('solicitud.lineas.0.articulo_id', $contexto['articulo']->id)
            ->where('solicitud.lineas.0.articulo', 'Tabla de pino')
            ->where('solicitud.lineas.0.cantidad_solicitada', 10)
            ->where('token', $contexto['token']));
    }

    public function test_an_unknown_token_returns_not_found(): void
    {
        // Act
        $response = $this->get('/portal/cotizaciones/'.Str::uuid());

        // Assert
        $response->assertNotFound();
    }

    public function test_an_expired_token_returns_not_found(): void
    {
        // Arrange
        $contexto = $this->solicitudConToken(today()->subDay()->toDateString());

        // Act
        $response = $this->get("/portal/cotizaciones/{$contexto['token']}");

        // Assert
        $response->assertNotFound();
    }

    public function test_a_quote_is_registered_and_the_token_is_invalidated(): void
    {
        // Arrange
        $contexto = $this->solicitudConToken(today()->addWeek()->toDateString());
        $payload = [
            'lineas' => [
                [
                    'articulo_id' => $contexto['articulo']->id,
                    'precio_unitario' => 120.5,
                    'cantidad_ofertada' => 8,
                    'fecha_prometida' => today()->addDays(5)->toDateString(),
                ],
            ],
        ];

        // Act
        $response = $this->post("/portal/cotizaciones/{$contexto['token']}", $payload);

        // Assert
        $response->assertRedirect("/portal/cotizaciones/{$contexto['token']}");
        $response->assertSessionHas('cotizacion_registrada', true);

        $this->assertDatabaseHas('cotizaciones', [
            'solicitud_de_cotizacion_id' => $contexto['solicitud']->id,
            'solicitud_de_disponibilidad_id' => null,
            'proveedor_id' => $contexto['proveedor']->id,
        ]);
        $this->assertDatabaseHas('lineas_de_cotizacion', [
            'articulo_id' => $contexto['articulo']->id,
            'precio_unitario' => 120.50,
            'cantidad_ofertada' => 8,
            'fecha_prometida' => today()->addDays(5)->toDateString(),
            'adjudicada' => false,
        ]);
        $this->assertDatabaseHas('solicitud_de_cotizacion_proveedor', [
            'solicitud_de_cotizacion_id' => $contexto['solicitud']->id,
            'proveedor_id' => $contexto['proveedor']->id,
            'token' => null,
        ]);
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'registrar_cotizacion',
        ]);

        // The same token cannot be used a second time.
        $this->post("/portal/cotizaciones/{$contexto['token']}", $payload)->assertNotFound();
    }

    public function test_rejects_an_article_that_was_not_requested(): void
    {
        // Arrange
        $contexto = $this->solicitudConToken(today()->addWeek()->toDateString());
        $ajeno = Articulo::factory()->create(['descripcion' => 'Artículo ajeno']);

        // Act
        $response = $this->post("/portal/cotizaciones/{$contexto['token']}", [
            'lineas' => [
                [
                    'articulo_id' => $ajeno->id,
                    'precio_unitario' => 100,
                    'cantidad_ofertada' => 5,
                    'fecha_prometida' => today()->addDays(5)->toDateString(),
                ],
            ],
        ]);

        // Assert
        $response->assertSessionHasErrors('lineas');
        $this->assertDatabaseCount('cotizaciones', 0);
    }

    public function test_requires_every_requested_article(): void
    {
        // Arrange
        $contexto = $this->solicitudConToken(today()->addWeek()->toDateString());
        $segundo = Articulo::factory()->create(['descripcion' => 'Barniz', 'tipo' => 'Insumo Consumible']);
        $contexto['solicitud']->lineas()->create([
            'articulo_id' => $segundo->id,
            'cantidad_solicitada' => 4,
        ]);

        // Act
        $response = $this->post("/portal/cotizaciones/{$contexto['token']}", [
            'lineas' => [
                [
                    'articulo_id' => $contexto['articulo']->id,
                    'precio_unitario' => 100,
                    'cantidad_ofertada' => 5,
                    'fecha_prometida' => today()->addDays(5)->toDateString(),
                ],
            ],
        ]);

        // Assert
        $response->assertSessionHasErrors('lineas');
        $this->assertDatabaseCount('cotizaciones', 0);
    }

    public function test_a_zero_price_fails_validation(): void
    {
        // Arrange
        $contexto = $this->solicitudConToken(today()->addWeek()->toDateString());

        // Act
        $response = $this->post("/portal/cotizaciones/{$contexto['token']}", [
            'lineas' => [
                [
                    'articulo_id' => $contexto['articulo']->id,
                    'precio_unitario' => 0,
                    'cantidad_ofertada' => 8,
                    'fecha_prometida' => today()->addDays(5)->toDateString(),
                ],
            ],
        ]);

        // Assert
        $response->assertSessionHasErrors('lineas.0.precio_unitario');
        $this->assertDatabaseCount('cotizaciones', 0);
    }

    public function test_a_zero_quantity_fails_validation(): void
    {
        // Arrange
        $contexto = $this->solicitudConToken(today()->addWeek()->toDateString());

        // Act
        $response = $this->post("/portal/cotizaciones/{$contexto['token']}", [
            'lineas' => [
                [
                    'articulo_id' => $contexto['articulo']->id,
                    'precio_unitario' => 100,
                    'cantidad_ofertada' => 0,
                    'fecha_prometida' => today()->addDays(5)->toDateString(),
                ],
            ],
        ]);

        // Assert
        $response->assertSessionHasErrors('lineas.0.cantidad_ofertada');
        $this->assertDatabaseCount('cotizaciones', 0);
    }

    public function test_a_past_promised_date_fails_validation(): void
    {
        // Arrange
        $contexto = $this->solicitudConToken(today()->addWeek()->toDateString());

        // Act
        $response = $this->post("/portal/cotizaciones/{$contexto['token']}", [
            'lineas' => [
                [
                    'articulo_id' => $contexto['articulo']->id,
                    'precio_unitario' => 100,
                    'cantidad_ofertada' => 8,
                    'fecha_prometida' => today()->subDay()->toDateString(),
                ],
            ],
        ]);

        // Assert
        $response->assertSessionHasErrors('lineas.0.fecha_prometida');
        $this->assertDatabaseCount('cotizaciones', 0);
    }

    public function test_a_quote_cannot_be_registered_with_an_expired_token(): void
    {
        // Arrange
        $contexto = $this->solicitudConToken(today()->subDay()->toDateString());

        // Act
        $response = $this->post("/portal/cotizaciones/{$contexto['token']}", [
            'lineas' => [
                [
                    'articulo_id' => $contexto['articulo']->id,
                    'precio_unitario' => 100,
                    'cantidad_ofertada' => 5,
                    'fecha_prometida' => today()->addDays(5)->toDateString(),
                ],
            ],
        ]);

        // Assert
        $response->assertNotFound();
        $this->assertDatabaseCount('cotizaciones', 0);
    }

    public function test_a_quote_cannot_be_registered_without_an_audited_owner(): void
    {
        // Arrange
        $solicitud = SolicitudDeCotizacionFactory::new()->create([
            'fecha_vencimiento' => today()->addWeek()->toDateString(),
        ]);
        $proveedor = Proveedor::factory()->create(['email' => 'ventas@aceros.test']);
        $articulo = Articulo::factory()->create(['descripcion' => 'Tabla de pino']);
        $solicitud->lineas()->create([
            'articulo_id' => $articulo->id,
            'cantidad_solicitada' => 10,
        ]);
        $token = (string) Str::uuid();
        $solicitud->proveedores()->attach($proveedor->id, ['token' => $token]);

        // Act
        $response = $this->post("/portal/cotizaciones/{$token}", [
            'lineas' => [
                [
                    'articulo_id' => $articulo->id,
                    'precio_unitario' => 100,
                    'cantidad_ofertada' => 5,
                    'fecha_prometida' => today()->addDays(5)->toDateString(),
                ],
            ],
        ]);

        // Assert
        $response->assertNotFound();
        $this->assertDatabaseCount('cotizaciones', 0);
    }

    public function test_a_price_above_the_maximum_fails_validation(): void
    {
        // Arrange
        $contexto = $this->solicitudConToken(today()->addWeek()->toDateString());

        // Act
        $response = $this->post("/portal/cotizaciones/{$contexto['token']}", [
            'lineas' => [
                [
                    'articulo_id' => $contexto['articulo']->id,
                    'precio_unitario' => 10000000,
                    'cantidad_ofertada' => 8,
                    'fecha_prometida' => today()->addDays(5)->toDateString(),
                ],
            ],
        ]);

        // Assert
        $response->assertSessionHasErrors('lineas.0.precio_unitario');
        $this->assertDatabaseCount('cotizaciones', 0);
    }

    public function test_a_quantity_above_the_maximum_fails_validation(): void
    {
        // Arrange
        $contexto = $this->solicitudConToken(today()->addWeek()->toDateString());

        // Act
        $response = $this->post("/portal/cotizaciones/{$contexto['token']}", [
            'lineas' => [
                [
                    'articulo_id' => $contexto['articulo']->id,
                    'precio_unitario' => 100,
                    'cantidad_ofertada' => 10000000,
                    'fecha_prometida' => today()->addDays(5)->toDateString(),
                ],
            ],
        ]);

        // Assert
        $response->assertSessionHasErrors('lineas.0.cantidad_ofertada');
        $this->assertDatabaseCount('cotizaciones', 0);
    }

    public function test_the_public_quote_form_is_rate_limited(): void
    {
        // Act / Assert
        for ($intento = 0; $intento < 20; $intento++) {
            $this->get('/portal/cotizaciones/'.Str::uuid())->assertNotFound();
        }

        $this->get('/portal/cotizaciones/'.Str::uuid())->assertStatus(429);
    }

    public function test_the_public_quote_submission_is_rate_limited(): void
    {
        // Arrange
        $payload = [
            'lineas' => [
                [
                    'articulo_id' => 1,
                    'precio_unitario' => 100,
                    'cantidad_ofertada' => 5,
                    'fecha_prometida' => today()->addDays(5)->toDateString(),
                ],
            ],
        ];

        // Act / Assert
        for ($intento = 0; $intento < 20; $intento++) {
            $this->post('/portal/cotizaciones/'.Str::uuid(), $payload)->assertNotFound();
        }

        $this->post('/portal/cotizaciones/'.Str::uuid(), $payload)->assertStatus(429);
    }
}
