<?php

namespace Tests\Feature\Shared\Alertas;

use App\Modules\Inventario\Models\Articulo;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use App\Shared\Alertas\Models\Alerta;
use App\Shared\Alertas\Models\ReglaDeAlerta;
use App\Shared\Alertas\Queries\AlertaQuery;
use App\Shared\Alertas\Services\AlertaService;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AlertaEmisionTest extends TestCase
{
    use RefreshDatabase;

    private function service(): AlertaService
    {
        return app(AlertaService::class);
    }

    /**
     * @param  array<int, Rol>  $roles
     * @param  array<string, mixed>  $atributos
     */
    private function regla(array $roles = [Rol::EncargadoStock], array $atributos = []): ReglaDeAlerta
    {
        $this->seed(RoleSeeder::class);

        $regla = ReglaDeAlerta::create(array_merge([
            'nombre' => 'Regla '.Str::random(6),
            'tipo' => 'stock_bajo_minimo',
            'modulo' => 'Inventario',
            'severidad' => 'Atencion',
            'origen_tipo' => 'condicion',
            'condiciones' => [],
            'mensaje' => '{{articulo}} bajo mínimo: {{disponible}} de {{minimo}}.',
            'activa' => true,
        ], $atributos));

        $regla->roles()->sync(
            collect($roles)->map(fn (Rol $rol): int => Role::findByName($rol->value, 'web')->id)->all(),
        );

        return $regla;
    }

    private function usuarioConRol(Rol $rol): Usuario
    {
        $this->seed(RoleSeeder::class);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole($rol->value);

        return $usuario;
    }

    public function test_it_emits_one_alert_with_roles_and_rendered_message(): void
    {
        // Arrange
        $regla = $this->regla();
        $articulo = Articulo::factory()->create();
        $contexto = [
            'articulo' => 'Tabla de pino',
            'disponible' => 3,
            'minimo' => 10,
            'articulo_id' => $articulo->id,
        ];

        // Act
        $this->service()->emitir($regla, $contexto, 'articulo:'.$articulo->id);

        // Assert
        $alerta = Alerta::query()->firstOrFail();
        $this->assertSame('Tabla de pino bajo mínimo: 3 de 10.', $alerta->descripcion);
        $this->assertSame('Atencion', $alerta->severidad);
        $this->assertSame($articulo->id, $alerta->articulo_id);
        $this->assertSame($regla->id, $alerta->regla_id);
        $this->assertTrue($alerta->roles()->where('roles.name', Rol::EncargadoStock->value)->exists());
    }

    public function test_the_same_open_condition_is_not_duplicated(): void
    {
        // Arrange
        $regla = $this->regla();
        $this->service()->emitir($regla, ['articulo' => 'Tabla', 'disponible' => 3, 'minimo' => 10], 'articulo:7');

        // Act
        $this->service()->emitir($regla, ['articulo' => 'Tabla', 'disponible' => 2, 'minimo' => 10], 'articulo:7');

        // Assert
        $this->assertSame(1, Alerta::query()->count());
        $this->assertStringContainsString('2 de 10', Alerta::query()->firstOrFail()->descripcion);
    }

    public function test_resolving_allows_a_new_alert_for_the_same_reference(): void
    {
        // Arrange
        $regla = $this->regla();
        $this->service()->emitir($regla, ['articulo' => 'Tabla'], 'articulo:7');

        // Act
        $this->service()->resolver($regla, 'articulo:7');
        $this->service()->emitir($regla, ['articulo' => 'Tabla'], 'articulo:7');

        // Assert
        $this->assertSame(2, Alerta::query()->count());
        $this->assertSame(1, Alerta::query()->whereNull('resuelta_en')->count());
    }

    public function test_inactive_rules_do_not_emit(): void
    {
        // Arrange
        $this->regla(atributos: ['activa' => false]);

        // Act
        $emitidas = $this->service()->emitirPorTipo('stock_bajo_minimo', ['articulo' => 'Tabla'], 'articulo:7');

        // Assert
        $this->assertSame(0, $emitidas);
        $this->assertSame(0, Alerta::query()->count());
    }

    public function test_two_rules_of_the_same_type_emit_two_alerts(): void
    {
        // Arrange
        $this->regla([Rol::EncargadoStock]);
        $this->regla([Rol::Gerente]);

        // Act
        $emitidas = $this->service()->emitirPorTipo('stock_bajo_minimo', ['articulo' => 'Tabla'], 'articulo:7');

        // Assert
        $this->assertSame(2, $emitidas);
        $this->assertSame(2, Alerta::query()->count());
    }

    public function test_visibility_and_read_state_are_per_user_and_role(): void
    {
        // Arrange
        $stock = $this->usuarioConRol(Rol::EncargadoStock);
        $gerente = $this->usuarioConRol(Rol::Gerente);
        $admin = $this->usuarioConRol(Rol::Administrador);
        $regla = $this->regla([Rol::EncargadoStock]);
        $alerta = $this->service()->emitir($regla, ['articulo' => 'Tabla'], 'articulo:7');
        $query = app(AlertaQuery::class);

        // Assert
        $this->assertSame(1, $query->noLeidas($stock));
        $this->assertSame(0, $query->noLeidas($gerente));
        $this->assertSame(1, $query->noLeidas($admin));

        // Act
        $this->service()->marcarLeida($stock, $alerta);

        // Assert
        $this->assertSame(0, $query->noLeidas($stock));
        $this->assertSame(1, $query->noLeidas($admin));

        // Act
        $this->service()->marcarTodasLeidas($admin);

        // Assert
        $this->assertSame(0, $query->noLeidas($admin));
    }

    public function test_old_alerts_can_be_archived(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoStock);
        $regla = $this->regla([Rol::EncargadoStock]);
        $alerta = $this->service()->emitir($regla, ['articulo' => 'Tabla'], 'articulo:7');
        $alerta->update(['fecha' => now()->subDays(100)]);
        $query = app(AlertaQuery::class);

        // Act
        $archivadas = $this->service()->archivarAntiguas(90);

        // Assert
        $this->assertSame(1, $archivadas);
        $this->assertSame(0, $query->noLeidas($usuario));
    }

    public function test_the_partial_unique_index_blocks_a_second_open_alert_with_the_same_key(): void
    {
        // Arrange
        Alerta::create([
            'tipo' => 'stock_bajo_minimo',
            'fecha' => now(),
            'descripcion' => 'Primera',
            'clave_dedupe' => '1:stock_bajo_minimo:articulo:7',
        ]);

        // Act / Assert
        try {
            Alerta::create([
                'tipo' => 'stock_bajo_minimo',
                'fecha' => now(),
                'descripcion' => 'Segunda',
                'clave_dedupe' => '1:stock_bajo_minimo:articulo:7',
            ]);

            $this->fail('The partial unique index must reject a duplicate open key.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }
    }
}
