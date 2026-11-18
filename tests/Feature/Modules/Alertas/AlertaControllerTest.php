<?php

namespace Tests\Feature\Modules\Alertas;

use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use App\Shared\Alertas\Models\Alerta;
use App\Shared\Alertas\Models\ReglaDeAlerta;
use App\Shared\Alertas\Queries\AlertaQuery;
use App\Shared\Alertas\Services\AlertaService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AlertaControllerTest extends TestCase
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
     * @param  array<int, Rol>  $roles
     */
    private function emitir(array $roles = [Rol::EncargadoStock]): Alerta
    {
        $regla = ReglaDeAlerta::create([
            'nombre' => 'Stock bajo '.uniqid(),
            'tipo' => 'stock_bajo_minimo',
            'modulo' => 'Inventario',
            'severidad' => 'Critica',
            'origen_tipo' => 'condicion',
            'condiciones' => [],
            'mensaje' => '{{articulo}} bajo mínimo.',
            'activa' => true,
        ]);

        $regla->roles()->sync(
            collect($roles)->map(fn (Rol $rol): int => Role::findByName($rol->value, 'web')->id)->all(),
        );

        return app(AlertaService::class)->emitir($regla, ['articulo' => 'Tabla'], 'articulo:1');
    }

    public function test_guests_are_redirected_to_the_login_screen(): void
    {
        // Act
        $response = $this->get('/alertas');

        // Assert
        $response->assertRedirect('/login');
    }

    public function test_the_inbox_shows_only_the_alerts_of_the_user_roles(): void
    {
        // Arrange
        $stock = $this->usuarioConRol(Rol::EncargadoStock);
        $this->emitir();

        // Act
        $response = $this->actingAs($stock)->get('/alertas');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Alertas/Index')
            ->has('alertas.data', 1)
            ->where('noLeidas', 1)
            ->where('alertas_no_leidas', 1)
            ->has('alertas_recientes', 1)
            ->where('alertas_recientes.0.tipo', 'stock_bajo_minimo')
        );
    }

    public function test_marking_one_alert_as_read_updates_the_counter(): void
    {
        // Arrange
        $stock = $this->usuarioConRol(Rol::EncargadoStock);
        $alerta = $this->emitir();

        // Act
        $response = $this->actingAs($stock)->patch("/alertas/{$alerta->id}/leida");

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('alerta_lecturas', [
            'alerta_id' => $alerta->id,
            'usuario_id' => $stock->id,
        ]);
        $this->assertSame(0, app(AlertaQuery::class)->noLeidas($stock));
    }

    public function test_marking_all_alerts_as_read(): void
    {
        // Arrange
        $stock = $this->usuarioConRol(Rol::EncargadoStock);
        $this->emitir();
        $this->emitir();

        // Act
        $response = $this->actingAs($stock)->patch('/alertas/leidas');

        // Assert
        $response->assertRedirect();
        $this->assertSame(0, app(AlertaQuery::class)->noLeidas($stock));
    }

    public function test_a_user_cannot_mark_an_alert_they_cannot_see(): void
    {
        // Arrange
        $gerente = $this->usuarioConRol(Rol::Gerente);
        $alerta = $this->emitir([Rol::EncargadoStock]);

        // Act
        $response = $this->actingAs($gerente)->patch("/alertas/{$alerta->id}/leida");

        // Assert
        $response->assertForbidden();
    }
}
