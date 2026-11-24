<?php

namespace Tests\Feature\Modules\Compras;

use App\Modules\Compras\Mail\SolicitudDeDisponibilidadMail;
use App\Modules\Compras\Models\Cotizacion;
use App\Modules\Compras\Models\DesempenoProveedor;
use App\Modules\Compras\Models\LineaCotizacion;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Services\GestionDisponibilidadService;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Predictivo\Services\MLEngine;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use App\Shared\Alertas\Models\Alerta;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReglaDeAlertaSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\Fakes\FakeMLEngine;
use Tests\TestCase;

class GestionDisponibilidadServiceTest extends TestCase
{
    use RefreshDatabase;

    private function servicio(): GestionDisponibilidadService
    {
        return app(GestionDisponibilidadService::class);
    }

    /**
     * @param  array<string, mixed>  $resultado
     */
    private function fake(array $resultado): FakeMLEngine
    {
        $fake = new FakeMLEngine($resultado);
        $this->app->instance(MLEngine::class, $fake);

        return $fake;
    }

    private function actor(): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class, ReglaDeAlertaSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole(Rol::EncargadoCompras->value);

        return $usuario;
    }

    private function articulo(): Articulo
    {
        return Articulo::factory()->create([
            'descripcion' => 'Tabla de pino',
            'tipo' => 'Materia Prima',
            'estado' => 'Activo',
        ]);
    }

    private function proveedor(?string $email = 'ventas@aceros.test'): Proveedor
    {
        return Proveedor::factory()->create([
            'razon_social' => 'Aceros del Sur',
            'email' => $email,
            'estado' => 'Activo',
        ]);
    }

    public function test_it_creates_a_request_with_a_token_and_sends_the_mail(): void
    {
        Mail::fake();
        $actor = $this->actor();
        $articulo = $this->articulo();
        $proveedor = $this->proveedor();

        $solicitud = $this->servicio()->crear($actor, $articulo->id, 50, $proveedor->id, '2026-12-01');

        $this->assertSame('Enviada', $solicitud->estado);
        $this->assertNotNull($solicitud->token);
        $this->assertSame('2026-12-01', $solicitud->fecha_requerida->toDateString());
        Mail::assertSent(SolicitudDeDisponibilidadMail::class);
        $this->assertDatabaseHas('eventos_de_auditoria', ['accion' => 'solicitar_disponibilidad']);
    }

    public function test_it_blocks_a_supplier_without_email(): void
    {
        Mail::fake();
        $actor = $this->actor();
        $articulo = $this->articulo();
        $proveedor = $this->proveedor(null);

        $this->expectException(ValidationException::class);

        $this->servicio()->crear($actor, $articulo->id, 50, $proveedor->id);
    }

    public function test_it_opens_the_request_by_token(): void
    {
        Mail::fake();
        $actor = $this->actor();
        $solicitud = $this->servicio()->crear($actor, $this->articulo()->id, 50, $this->proveedor()->id);

        $datos = $this->servicio()->obtenerPorToken((string) $solicitud->token);

        $this->assertNotNull($datos);
        $this->assertSame('Tabla de pino', $datos['articulo']);
        $this->assertSame(50.0, $datos['cantidad_solicitada']);
    }

    public function test_a_supplier_response_creates_the_flow_a_quote(): void
    {
        Mail::fake();
        $actor = $this->actor();
        $solicitud = $this->servicio()->crear($actor, $this->articulo()->id, 50, $this->proveedor()->id);

        $cotizacion = $this->servicio()->registrarRespuesta(
            (string) $solicitud->token,
            40,
            120.5,
            '2026-12-05',
        );

        $this->assertSame($solicitud->id, $cotizacion->solicitud_de_disponibilidad_id);
        $this->assertNull($cotizacion->solicitud_de_cotizacion_id);

        $linea = $cotizacion->lineas()->firstOrFail();
        $this->assertSame(40.0, (float) $linea->cantidad_ofertada);
        $this->assertSame(120.5, (float) $linea->precio_unitario);
        $this->assertNull($solicitud->refresh()->token);
    }

    public function test_a_used_token_cannot_answer_twice(): void
    {
        Mail::fake();
        $actor = $this->actor();
        $solicitud = $this->servicio()->crear($actor, $this->articulo()->id, 50, $this->proveedor()->id);
        $token = (string) $solicitud->token;

        $this->servicio()->registrarRespuesta($token, 40, 100, '2026-12-05');

        $this->expectException(ModelNotFoundException::class);

        $this->servicio()->registrarRespuesta($token, 10, 90, '2026-12-06');
    }

    public function test_it_lists_the_requests_with_their_response_state(): void
    {
        Mail::fake();
        $actor = $this->actor();
        $solicitud = $this->servicio()->crear($actor, $this->articulo()->id, 50, $this->proveedor()->id);

        $antes = $this->servicio()->listar();
        $this->assertFalse($antes[0]['respondida']);

        $this->servicio()->registrarRespuesta((string) $solicitud->token, 40, 100, '2026-12-05');

        $despues = $this->servicio()->listar();
        $this->assertTrue($despues[0]['respondida']);
        $this->assertSame('Aceros del Sur', $despues[0]['proveedor']);
    }

    public function test_it_marks_the_request_as_answered(): void
    {
        Mail::fake();
        $actor = $this->actor();
        $solicitud = $this->servicio()->crear($actor, $this->articulo()->id, 50, $this->proveedor()->id);

        $this->servicio()->marcarRespondida($solicitud, $actor->id);

        $this->assertSame('Respondida', $solicitud->refresh()->estado);
        $this->assertDatabaseHas('eventos_de_auditoria', ['accion' => 'resolver_disponibilidad']);
    }

    public function test_a_quote_is_linked_to_its_disponibilidad(): void
    {
        Mail::fake();
        $actor = $this->actor();
        $solicitud = $this->servicio()->crear($actor, $this->articulo()->id, 50, $this->proveedor()->id);

        $this->servicio()->registrarRespuesta((string) $solicitud->token, 40, 100, '2026-12-05');

        $this->assertSame(1, Cotizacion::query()->where('solicitud_de_disponibilidad_id', $solicitud->id)->count());
    }

    public function test_it_resolves_an_ideal_answer_as_respondida(): void
    {
        Mail::fake();
        $actor = $this->actor();
        $solicitud = $this->servicio()->crear($actor, $this->articulo()->id, 50, $this->proveedor()->id, '2026-12-10');

        $this->servicio()->registrarRespuesta((string) $solicitud->token, 40, 100, '2026-12-05');

        $resultado = $this->servicio()->resolver($solicitud, $actor->id);

        $this->assertSame('Ideal', $resultado['escenario']);
        $this->assertSame('Respondida', $solicitud->refresh()->estado);
        $this->assertNull($resultado['proveedor_sugerido']);
        $this->assertSame(0, Alerta::query()->count());
    }

    public function test_it_alerts_a_strong_price_variation(): void
    {
        Mail::fake();
        $actor = $this->actor();
        $articulo = $this->articulo();
        $proveedor = $this->proveedor();

        $previa = Cotizacion::factory()->create([
            'proveedor_id' => $proveedor->id,
            'fecha' => now()->subDays(10)->toDateString(),
        ]);
        LineaCotizacion::factory()->create([
            'cotizacion_id' => $previa->id,
            'articulo_id' => $articulo->id,
            'precio_unitario' => 100,
        ]);

        $solicitud = $this->servicio()->crear($actor, $articulo->id, 50, $proveedor->id, '2026-12-10');
        $this->servicio()->registrarRespuesta((string) $solicitud->token, 40, 200, '2026-12-05');

        $fake = $this->fake([
            'escenario' => 'Variación Fuerte',
            'motivos' => ['precio_sobre_umbral'],
            'desviacion_precio' => 1.0,
            'dias_de_atraso' => -5,
        ]);

        $resultado = $this->servicio()->resolver($solicitud, $actor->id);

        $this->assertSame(100.0, $fake->llamadas[0]['datos']['precio_referencia']);
        $this->assertSame(200.0, $fake->llamadas[0]['datos']['precio_unitario']);
        $this->assertSame('Variación Fuerte', $resultado['escenario']);
        $this->assertContains('precio_sobre_umbral', $resultado['motivos']);

        $alerta = Alerta::query()->where('tipo', 'variacion_fuerte_disponibilidad')->firstOrFail();
        $this->assertSame($articulo->id, $alerta->articulo_id);
    }

    public function test_it_alerts_a_date_beyond_the_needed_date(): void
    {
        Mail::fake();
        $actor = $this->actor();
        $solicitud = $this->servicio()->crear($actor, $this->articulo()->id, 50, $this->proveedor()->id, '2026-12-01');

        $this->servicio()->registrarRespuesta((string) $solicitud->token, 40, 100, '2026-12-10');

        $fake = $this->fake([
            'escenario' => 'Variación Fuerte',
            'motivos' => ['fecha_fuera_del_cronograma'],
            'desviacion_precio' => null,
            'dias_de_atraso' => 4,
        ]);

        $resultado = $this->servicio()->resolver($solicitud, $actor->id);

        $this->assertSame('2026-12-10', $fake->llamadas[0]['datos']['fecha_prometida']);
        $this->assertSame('2026-12-01', $fake->llamadas[0]['datos']['fecha_requerida']);
        $this->assertSame('Variación Fuerte', $resultado['escenario']);
        $this->assertContains('fecha_fuera_del_cronograma', $resultado['motivos']);
        $this->assertSame(4, $resultado['dias_de_atraso']);
    }

    public function test_a_no_stock_answer_suggests_the_next_ranked_supplier(): void
    {
        Mail::fake();
        $actor = $this->actor();
        $actual = $this->proveedor();

        $alternativo = Proveedor::factory()->create([
            'razon_social' => 'Maderas del Norte',
            'email' => 'ventas@maderas.test',
            'estado' => 'Activo',
        ]);
        DesempenoProveedor::factory()->create([
            'proveedor_id' => $alternativo->id,
            'tiempo_entrega_real' => 5,
            'fecha_registro' => now()->subDays(5)->toDateString(),
        ]);

        $solicitud = $this->servicio()->crear($actor, $this->articulo()->id, 50, $actual->id);
        $this->servicio()->registrarRespuesta((string) $solicitud->token, 0, 100, '2026-12-05');

        $this->fake([
            'escenario' => 'Sin Stock',
            'motivos' => ['sin_stock'],
            'desviacion_precio' => null,
            'dias_de_atraso' => null,
        ]);

        $resultado = $this->servicio()->resolver($solicitud, $actor->id);

        $this->assertSame('Sin Stock', $resultado['escenario']);
        $this->assertNotNull($resultado['proveedor_sugerido']);
        $this->assertSame($alternativo->id, $resultado['proveedor_sugerido']['id']);
    }
}
