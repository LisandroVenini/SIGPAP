<?php

namespace Tests\Feature\Modules\Alertas;

use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use App\Shared\Alertas\Models\ReglaDeAlerta;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReglaDeAlertaControllerTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioConRol(Rol $rol): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole($rol->value);

        return $usuario;
    }

    /**
     * @param  array<string, mixed>  $condiciones
     * @param  array<int, Rol>  $roles
     * @return array<string, mixed>
     */
    private function payload(string $tipo, array $condiciones = [], array $roles = [Rol::EncargadoCompras]): array
    {
        return [
            'nombre' => 'Regla '.uniqid(),
            'descripcion' => 'Regla de prueba',
            'tipo' => $tipo,
            'severidad' => 'Atencion',
            'mensaje' => 'Aviso de {{numero}} en {{dias}} días.',
            'activa' => true,
            'roles' => collect($roles)->map(fn (Rol $rol): int => Role::findByName($rol->value, 'web')->id)->all(),
            'condiciones' => $condiciones,
        ];
    }

    /**
     * Validator keys with dots (condiciones.dias) are stored flat, so assert on keys().
     */
    private function assertError($response, string $clave): void
    {
        $response->assertInvalid([$clave]);
    }

    public function test_guests_are_redirected_to_the_login_screen(): void
    {
        // Act
        $response = $this->get('/alertas/reglas');

        // Assert
        $response->assertRedirect('/login');
    }

    public function test_the_rules_page_renders_with_the_catalogue(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoCompras);

        // Act
        $response = $this->actingAs($usuario)->get('/alertas/reglas');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Alertas/Reglas/Index')
            ->has('reglas.data')
            ->has('tipos', 15)
            ->has('roles')
            ->where('puedeGestionar', true)
        );
    }

    public function test_an_encargado_creates_a_rule_for_their_module(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoCompras);

        // Act
        $response = $this->actingAs($usuario)->post('/alertas/reglas', $this->payload(
            'solicitud_por_vencer',
            ['dias' => 5],
        ));

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('reglas_de_alerta', [
            'tipo' => 'solicitud_por_vencer',
            'modulo' => 'Compras',
            'creada_por' => $usuario->id,
        ]);
    }

    public function test_an_encargado_cannot_create_a_rule_for_another_module(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoCompras);

        // Act
        $response = $this->actingAs($usuario)->post('/alertas/reglas', $this->payload(
            'orden_por_vencer',
            ['dias' => 2, 'avance_maximo' => 100],
            [Rol::SupervisorProduccion],
        ));

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseCount('reglas_de_alerta', 0);
    }

    public function test_a_gerente_can_create_a_rule_for_any_module(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::Gerente);
        $payload = $this->payload(
            'orden_por_vencer',
            ['dias' => 2, 'avance_maximo' => 100],
            [Rol::SupervisorProduccion],
        );
        $payload['mensaje'] = 'La orden {{orden}} de {{producto}} vence el {{fecha_entrega}}.';

        // Act
        $response = $this->actingAs($usuario)->post('/alertas/reglas', $payload);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('reglas_de_alerta', ['modulo' => 'Produccion']);
    }

    public function test_conditions_outside_the_allowed_range_are_rejected(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoCompras);

        // Act
        $response = $this->actingAs($usuario)->post('/alertas/reglas', $this->payload(
            'solicitud_por_vencer',
            ['dias' => 999],
        ));

        // Assert
        $this->assertError($response, 'condiciones.dias');
        $this->assertDatabaseCount('reglas_de_alerta', 0);
    }

    public function test_unknown_template_variables_are_rejected(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoCompras);
        $payload = $this->payload('solicitud_por_vencer', ['dias' => 5]);
        $payload['mensaje'] = 'Aviso {{inventada}}';

        // Act
        $response = $this->actingAs($usuario)->post('/alertas/reglas', $payload);

        // Assert
        $this->assertError($response, 'mensaje');
        $this->assertDatabaseCount('reglas_de_alerta', 0);
    }

    public function test_only_the_creator_or_management_can_update(): void
    {
        // Arrange
        $creador = $this->usuarioConRol(Rol::EncargadoCompras);
        $otro = $this->usuarioConRol(Rol::EncargadoCompras);
        $this->actingAs($creador)->post('/alertas/reglas', $this->payload(
            'solicitud_por_vencer',
            ['dias' => 5],
        ));
        $regla = ReglaDeAlerta::query()->firstOrFail();

        // Act
        $ajeno = $this->actingAs($otro)->put("/alertas/reglas/{$regla->id}", $this->payload(
            'solicitud_por_vencer',
            ['dias' => 6],
        ));

        $propio = $this->actingAs($creador)->put("/alertas/reglas/{$regla->id}", $this->payload(
            'solicitud_por_vencer',
            ['dias' => 6],
        ));

        // Assert
        $ajeno->assertForbidden();
        $propio->assertRedirect();
        $this->assertSame(6, $regla->refresh()->condiciones['dias']);
    }

    public function test_deleting_a_rule_is_soft_and_toggles_its_state(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol(Rol::EncargadoCompras);
        $this->actingAs($usuario)->post('/alertas/reglas', $this->payload('solicitud_por_vencer', ['dias' => 5]));
        $regla = ReglaDeAlerta::query()->firstOrFail();

        // Act
        $this->actingAs($usuario)->patch("/alertas/reglas/{$regla->id}/estado");

        // Assert
        $this->assertFalse($regla->refresh()->activa);

        // Act
        $this->actingAs($usuario)->delete("/alertas/reglas/{$regla->id}");

        // Assert
        $this->assertSoftDeleted('reglas_de_alerta', ['id' => $regla->id]);
    }
}
