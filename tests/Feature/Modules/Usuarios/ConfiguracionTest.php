<?php

namespace Tests\Feature\Modules\Usuarios;

use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\ParametroGlobal;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\ParametroGlobalSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ConfiguracionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_screen(): void
    {
        // Act
        $response = $this->get('/usuarios/configuracion');

        // Assert
        $response->assertRedirect('/login');
    }

    public function test_usuarios_without_an_allowed_role_cannot_view_the_configuration(): void
    {
        // Arrange
        $this->seed(ParametroGlobalSeeder::class);
        $usuario = Usuario::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->get('/usuarios/configuracion');

        // Assert
        $response->assertForbidden();
    }

    public function test_an_administrador_can_view_and_edit_the_configuration(): void
    {
        // Arrange
        $this->seed([RoleSeeder::class, ParametroGlobalSeeder::class]);
        $admin = Usuario::factory()->create();
        $admin->assignRole(Rol::Administrador->value);

        // Act
        $response = $this->actingAs($admin)->get('/usuarios/configuracion');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Usuarios/Configuracion')
            ->has('parametros')
            ->where('puedeEditar', true)
        );
    }

    public function test_a_gerente_can_view_but_not_edit_the_configuration(): void
    {
        // Arrange
        $this->seed([RoleSeeder::class, ParametroGlobalSeeder::class]);
        $gerente = Usuario::factory()->create();
        $gerente->assignRole(Rol::Gerente->value);

        // Act / Assert
        $this->actingAs($gerente)->get('/usuarios/configuracion')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('puedeEditar', false));

        $this->actingAs($gerente)->put('/usuarios/configuracion', [
            'parametros' => ['compras_vigencia_cotizacion_dias' => '10'],
        ])->assertForbidden();
    }

    public function test_an_administrador_can_update_parameters_and_the_change_is_audited(): void
    {
        // Arrange
        $this->seed([RoleSeeder::class, ParametroGlobalSeeder::class]);
        $admin = Usuario::factory()->create();
        $admin->assignRole(Rol::Administrador->value);

        // Act
        $response = $this->actingAs($admin)->put('/usuarios/configuracion', [
            'parametros' => ['compras_vigencia_cotizacion_dias' => '10'],
        ]);

        // Assert
        $response->assertRedirect();
        $this->assertSame('10', ParametroGlobal::where('nombre', 'compras_vigencia_cotizacion_dias')->value('valor'));
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'usuario_id' => $admin->id,
            'accion' => 'actualizar_parametro',
        ]);
    }
}
