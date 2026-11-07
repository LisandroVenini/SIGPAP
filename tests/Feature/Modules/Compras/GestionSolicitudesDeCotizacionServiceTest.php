<?php

namespace Tests\Feature\Modules\Compras;

use App\Modules\Compras\Mail\SolicitudDeCotizacionMail;
use App\Modules\Compras\Models\Cotizacion;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Models\SolicitudDeCotizacion;
use App\Modules\Compras\Services\GestionCotizacionesService;
use App\Modules\Compras\Services\GestionSolicitudesDeCotizacionService;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Factories\CotizacionFactory;
use Database\Factories\LineaSolicitudCotizacionFactory;
use Database\Factories\SolicitudDeCotizacionFactory;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GestionSolicitudesDeCotizacionServiceTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole(Rol::EncargadoCompras->value);

        return $usuario;
    }

    private function service(): GestionSolicitudesDeCotizacionService
    {
        return app(GestionSolicitudesDeCotizacionService::class);
    }

    private function articulo(string $descripcion): Articulo
    {
        return Articulo::factory()->create([
            'descripcion' => $descripcion,
            'tipo' => 'Materia Prima',
            'estado' => 'Activo',
        ]);
    }

    private function proveedor(string $razonSocial, ?string $email): Proveedor
    {
        return Proveedor::factory()->create([
            'razon_social' => $razonSocial,
            'email' => $email,
        ]);
    }

    public function test_creates_a_solicitud_with_lines_distinct_tokens_and_mails(): void
    {
        // Arrange
        Mail::fake();
        $actor = $this->actor();
        $tabla = $this->articulo('Tabla de pino');
        $tornillo = $this->articulo('Tornillo');
        $aceros = $this->proveedor('Aceros del Sur', 'ventas@aceros.test');
        $maderas = $this->proveedor('Maderas Norte', 'ventas@maderas.test');

        // Act
        $solicitud = $this->service()->crear($actor, [
            'fecha_vencimiento' => today()->addWeek()->toDateString(),
            'lineas' => [
                ['articulo_id' => $tabla->id, 'cantidad_solicitada' => 10],
                ['articulo_id' => $tornillo->id, 'cantidad_solicitada' => 25],
            ],
            'proveedores' => [$aceros->id, $maderas->id],
        ], '127.0.0.1');

        // Assert
        $this->assertSame('Enviada', $solicitud->estado);
        $this->assertSame(today()->toDateString(), $solicitud->fecha->toDateString());
        $this->assertDatabaseCount('lineas_de_solicitud_de_cotizacion', 2);
        $this->assertDatabaseCount('solicitud_de_cotizacion_proveedor', 2);

        $tokens = $solicitud->proveedores()->get()
            ->map(fn (Proveedor $proveedor) => $proveedor->pivot->token)
            ->all();

        $this->assertCount(2, $tokens);
        $this->assertNotNull($tokens[0]);
        $this->assertNotNull($tokens[1]);
        $this->assertNotSame($tokens[0], $tokens[1]);

        Mail::assertSent(SolicitudDeCotizacionMail::class, 2);
        Mail::assertSent(
            SolicitudDeCotizacionMail::class,
            fn (SolicitudDeCotizacionMail $mail) => $mail->hasTo('ventas@aceros.test')
                && $mail->envelope()->subject === 'Solicitud de cotización SIGPAP'
                && collect($mail->lineas)->pluck('articulo')->sort()->values()->all() === ['Tabla de pino', 'Tornillo'],
        );

        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'crear_solicitud_cotizacion',
            'usuario_id' => $actor->id,
            'auditable_type' => SolicitudDeCotizacion::class,
            'auditable_id' => $solicitud->id,
        ]);
    }

    public function test_rejects_a_provider_without_email_and_creates_nothing(): void
    {
        // Arrange
        Mail::fake();
        $actor = $this->actor();
        $tabla = $this->articulo('Tabla de pino');
        $sinEmail = $this->proveedor('Proveedor sin correo', null);

        // Act / Assert
        try {
            $this->service()->crear($actor, [
                'fecha_vencimiento' => today()->addWeek()->toDateString(),
                'lineas' => [
                    ['articulo_id' => $tabla->id, 'cantidad_solicitada' => 10],
                ],
                'proveedores' => [$sinEmail->id],
            ]);

            $this->fail('A provider without email should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('proveedores', $exception->errors());
        }

        $this->assertDatabaseCount('solicitudes_de_cotizacion', 0);
        $this->assertDatabaseCount('lineas_de_solicitud_de_cotizacion', 0);
        $this->assertDatabaseCount('solicitud_de_cotizacion_proveedor', 0);
        Mail::assertNothingSent();
    }

    public function test_lists_solicitudes_with_counts_and_fifteen_per_page(): void
    {
        // Arrange
        for ($dia = 1; $dia <= 16; $dia++) {
            SolicitudDeCotizacionFactory::new()->create([
                'fecha' => sprintf('2026-01-%02d', $dia),
                'estado' => 'Enviada',
            ]);
        }

        $solicitud = SolicitudDeCotizacionFactory::new()->create([
            'fecha' => '2026-02-01',
            'estado' => 'Enviada',
        ]);
        LineaSolicitudCotizacionFactory::new()->count(2)->create([
            'solicitud_de_cotizacion_id' => $solicitud->id,
        ]);
        $proveedor = $this->proveedor('Aceros del Sur', 'ventas@aceros.test');
        $solicitud->proveedores()->attach($proveedor->id, ['token' => null]);
        CotizacionFactory::new()->create([
            'solicitud_de_cotizacion_id' => $solicitud->id,
            'proveedor_id' => $proveedor->id,
        ]);

        // Act
        $resultados = $this->service()->listar([]);

        // Assert
        $this->assertSame(15, $resultados->perPage());
        $this->assertSame(17, $resultados->total());
        $this->assertSame($solicitud->id, $resultados->items()[0]['id']);
        $this->assertSame(
            ['id', 'fecha', 'fecha_vencimiento', 'estado', 'lineas', 'proveedores', 'cotizaciones'],
            array_keys($resultados->items()[0]),
        );
        $this->assertSame(2, $resultados->items()[0]['lineas']);
        $this->assertSame(1, $resultados->items()[0]['proveedores']);
        $this->assertSame(1, $resultados->items()[0]['cotizaciones']);
    }

    public function test_lists_solicitudes_filtered_by_state_provider_and_date_range(): void
    {
        // Arrange
        $aceros = $this->proveedor('Aceros del Sur', 'ventas@aceros.test');
        $maderas = $this->proveedor('Maderas Norte', 'ventas@maderas.test');

        $enero = SolicitudDeCotizacionFactory::new()->create([
            'fecha' => '2026-01-10',
            'estado' => 'Enviada',
        ]);
        $enero->proveedores()->attach($aceros->id, ['token' => null]);

        $febrero = SolicitudDeCotizacionFactory::new()->create([
            'fecha' => '2026-02-10',
            'estado' => 'Anulada',
        ]);
        $febrero->proveedores()->attach($maderas->id, ['token' => null]);

        $marzo = SolicitudDeCotizacionFactory::new()->create([
            'fecha' => '2026-03-10',
            'estado' => 'Enviada',
        ]);
        $marzo->proveedores()->attach($aceros->id, ['token' => null]);

        // Act
        $porEstado = $this->service()->listar(['estado' => 'Enviada']);
        $porProveedor = $this->service()->listar(['proveedor' => 'acer']);
        $porRango = $this->service()->listar([
            'fecha_desde' => '2026-02-01',
            'fecha_hasta' => '2026-02-28',
        ]);

        // Assert
        $this->assertSame(2, $porEstado->total());
        $this->assertSame([$marzo->id, $enero->id], array_column($porEstado->items(), 'id'));

        $this->assertSame(2, $porProveedor->total());
        $this->assertSame([$marzo->id, $enero->id], array_column($porProveedor->items(), 'id'));

        $this->assertSame(1, $porRango->total());
        $this->assertSame($febrero->id, $porRango->items()[0]['id']);
    }

    public function test_updates_a_solicitud_replacing_lines_and_providers_and_mails_only_new_ones(): void
    {
        // Arrange
        Mail::fake();
        $actor = $this->actor();
        $tabla = $this->articulo('Tabla de pino');
        $tornillo = $this->articulo('Tornillo');
        $aceros = $this->proveedor('Aceros del Sur', 'ventas@aceros.test');
        $maderas = $this->proveedor('Maderas Norte', 'ventas@maderas.test');
        $clavos = $this->proveedor('Clavos del Litoral', 'ventas@clavos.test');

        $solicitud = $this->service()->crear($actor, [
            'fecha_vencimiento' => today()->addWeek()->toDateString(),
            'lineas' => [
                ['articulo_id' => $tabla->id, 'cantidad_solicitada' => 10],
            ],
            'proveedores' => [$aceros->id, $maderas->id],
        ]);

        $tokensAntes = $solicitud->proveedores()->get()
            ->mapWithKeys(fn (Proveedor $proveedor) => [$proveedor->id => $proveedor->pivot->token])
            ->all();

        Mail::fake();

        // Act
        $this->service()->actualizar($actor, $solicitud, [
            'fecha_vencimiento' => today()->addDays(10)->toDateString(),
            'lineas' => [
                ['articulo_id' => $tornillo->id, 'cantidad_solicitada' => 7],
            ],
            'proveedores' => [$maderas->id, $clavos->id],
        ], '127.0.0.1');

        // Assert
        $solicitud->refresh();
        $this->assertSame(today()->addDays(10)->toDateString(), $solicitud->fecha_vencimiento->toDateString());
        $this->assertDatabaseCount('lineas_de_solicitud_de_cotizacion', 1);
        $this->assertDatabaseHas('lineas_de_solicitud_de_cotizacion', [
            'solicitud_de_cotizacion_id' => $solicitud->id,
            'articulo_id' => $tornillo->id,
        ]);

        $tokensDespues = $solicitud->proveedores()->get()
            ->mapWithKeys(fn (Proveedor $proveedor) => [$proveedor->id => $proveedor->pivot->token])
            ->all();

        $this->assertArrayHasKey($maderas->id, $tokensDespues);
        $this->assertArrayHasKey($clavos->id, $tokensDespues);
        $this->assertArrayNotHasKey($aceros->id, $tokensDespues);
        $this->assertSame($tokensAntes[$maderas->id], $tokensDespues[$maderas->id]);
        $this->assertNotNull($tokensDespues[$clavos->id]);

        Mail::assertSent(SolicitudDeCotizacionMail::class, 1);
        Mail::assertSent(
            SolicitudDeCotizacionMail::class,
            fn (SolicitudDeCotizacionMail $mail) => $mail->proveedor->id === $clavos->id,
        );

        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'actualizar_solicitud_cotizacion',
            'usuario_id' => $actor->id,
            'auditable_type' => SolicitudDeCotizacion::class,
            'auditable_id' => $solicitud->id,
        ]);
    }

    public function test_blocks_update_when_the_solicitud_has_cotizaciones(): void
    {
        // Arrange
        $actor = $this->actor();
        $solicitud = SolicitudDeCotizacionFactory::new()->create([
            'fecha_vencimiento' => today()->addWeek()->toDateString(),
            'estado' => 'Enviada',
        ]);
        CotizacionFactory::new()->create([
            'solicitud_de_cotizacion_id' => $solicitud->id,
        ]);
        $articulo = $this->articulo('Tabla de pino');
        $proveedor = $this->proveedor('Aceros del Sur', 'ventas@aceros.test');

        // Act / Assert
        try {
            $this->service()->actualizar($actor, $solicitud, [
                'fecha_vencimiento' => today()->addDays(10)->toDateString(),
                'lineas' => [
                    ['articulo_id' => $articulo->id, 'cantidad_solicitada' => 3],
                ],
                'proveedores' => [$proveedor->id],
            ]);

            $this->fail('A solicitud with cotizaciones should not be updated.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('solicitud', $exception->errors());
        }

        $this->assertDatabaseCount('lineas_de_solicitud_de_cotizacion', 0);
    }

    public function test_blocks_annulment_when_the_solicitud_is_adjudicada(): void
    {
        // Arrange
        $actor = $this->actor();
        $solicitud = SolicitudDeCotizacionFactory::new()->create(['estado' => 'Adjudicada']);

        // Act / Assert
        try {
            $this->service()->anular($actor, $solicitud, 'Sin stock');

            $this->fail('An adjudicated solicitud should not be annulled.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('estado', $exception->errors());
        }

        $this->assertSame('Adjudicada', $solicitud->refresh()->estado);
    }

    public function test_blocks_annulment_when_the_solicitud_is_already_annulled(): void
    {
        // Arrange
        $actor = $this->actor();
        $solicitud = SolicitudDeCotizacionFactory::new()->create(['estado' => 'Anulada']);

        // Act / Assert
        try {
            $this->service()->anular($actor, $solicitud);

            $this->fail('An annulled solicitud should not be annulled again.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('estado', $exception->errors());
        }

        $this->assertSame('Anulada', $solicitud->refresh()->estado);
    }

    public function test_annuls_a_solicitud_and_audits_it(): void
    {
        // Arrange
        $actor = $this->actor();
        $solicitud = SolicitudDeCotizacionFactory::new()->create(['estado' => 'Enviada']);

        // Act
        $this->service()->anular($actor, $solicitud, 'El proveedor cerró el catálogo', '127.0.0.1');

        // Assert
        $this->assertSame('Anulada', $solicitud->refresh()->estado);
        $this->assertDatabaseHas('solicitudes_de_cotizacion', [
            'id' => $solicitud->id,
            'estado' => 'Anulada',
        ]);
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'anular_solicitud_cotizacion',
            'usuario_id' => $actor->id,
            'auditable_type' => SolicitudDeCotizacion::class,
            'auditable_id' => $solicitud->id,
        ]);
    }

    public function test_only_purchasing_manager_permission_can_manage_solicitudes(): void
    {
        // Arrange
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $encargado = Usuario::factory()->create();
        $encargado->assignRole(Rol::EncargadoCompras->value);

        $gerente = Usuario::factory()->create();
        $gerente->assignRole(Rol::Gerente->value);

        // Act / Assert
        $this->assertTrue($this->service()->puedeGestionar($encargado));
        $this->assertFalse($this->service()->puedeGestionar($gerente));
    }

    public function test_returns_the_cotizacion_model_from_registrar(): void
    {
        // Arrange
        Mail::fake();
        $actor = $this->actor();
        $articulo = $this->articulo('Tabla de pino');
        $proveedor = $this->proveedor('Aceros del Sur', 'ventas@aceros.test');
        $solicitud = $this->service()->crear($actor, [
            'fecha_vencimiento' => today()->addWeek()->toDateString(),
            'lineas' => [
                ['articulo_id' => $articulo->id, 'cantidad_solicitada' => 10],
            ],
            'proveedores' => [$proveedor->id],
        ]);
        $token = (string) $solicitud->proveedores()->first()->pivot->token;

        // Act
        $cotizacion = app(GestionCotizacionesService::class)->registrar(
            $token,
            [[
                'articulo_id' => $articulo->id,
                'precio_unitario' => 100,
                'cantidad_ofertada' => 10,
                'fecha_prometida' => today()->addDays(3)->toDateString(),
            ]],
            '127.0.0.1',
        );

        // Assert
        $this->assertInstanceOf(Cotizacion::class, $cotizacion);
    }

    public function test_registrar_attributes_the_quote_to_the_latest_audited_owner(): void
    {
        // Arrange
        Mail::fake();
        $primero = $this->actor();
        $segundo = Usuario::factory()->create();
        $segundo->assignRole(Rol::EncargadoCompras->value);

        $articulo = $this->articulo('Tabla de pino');
        $proveedor = $this->proveedor('Aceros del Sur', 'ventas@aceros.test');

        $solicitud = $this->service()->crear($primero, [
            'fecha_vencimiento' => today()->addWeek()->toDateString(),
            'lineas' => [
                ['articulo_id' => $articulo->id, 'cantidad_solicitada' => 10],
            ],
            'proveedores' => [$proveedor->id],
        ]);

        $this->service()->actualizar($segundo, $solicitud, [
            'fecha_vencimiento' => today()->addDays(10)->toDateString(),
            'lineas' => [
                ['articulo_id' => $articulo->id, 'cantidad_solicitada' => 10],
            ],
            'proveedores' => [$proveedor->id],
        ]);

        $token = (string) $solicitud->proveedores()->first()->pivot->token;

        // Act
        $cotizacion = app(GestionCotizacionesService::class)->registrar(
            $token,
            [[
                'articulo_id' => $articulo->id,
                'precio_unitario' => 100,
                'cantidad_ofertada' => 10,
                'fecha_prometida' => today()->addDays(3)->toDateString(),
            ]],
        );

        // Assert
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'registrar_cotizacion',
            'usuario_id' => $segundo->id,
            'auditable_type' => Cotizacion::class,
            'auditable_id' => $cotizacion->id,
        ]);
    }
}
