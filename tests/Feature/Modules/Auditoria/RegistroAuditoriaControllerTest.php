<?php

namespace Tests\Feature\Modules\Auditoria;

use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Factories\EventoDeAuditoriaFactory;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class RegistroAuditoriaControllerTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioConRol(Rol $rol): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole($rol->value);

        return $usuario;
    }

    public function test_guests_are_redirected_to_the_login_screen(): void
    {
        // Act
        $response = $this->get('/auditoria');

        // Assert
        $response->assertRedirect('/login');
    }

    public function test_users_without_the_permission_are_forbidden(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();

        // Act
        $response = $this->actingAs($usuario)->get('/auditoria');

        // Assert
        $response->assertForbidden();
    }

    public function test_an_administrador_can_view_the_audit_log(): void
    {
        // Arrange
        $admin = $this->usuarioConRol(Rol::Administrador);
        $autor = Usuario::factory()->create(['nombre' => 'Laura Cáceres']);

        EventoDeAuditoriaFactory::new()->create([
            'usuario_id' => $autor->id,
            'accion' => 'crear_usuario',
            'direccion_ip' => '192.168.0.10',
            'estado_anterior' => ['estado' => 'Inhabilitado'],
            'estado_nuevo' => ['estado' => 'Activo'],
        ]);

        // Act
        $response = $this->actingAs($admin)->get('/auditoria');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Auditoria/Index')
            ->has('eventos.data', 1)
            ->where('eventos.data.0.accion', 'crear_usuario')
            ->where('eventos.data.0.direccion_ip', '192.168.0.10')
            ->has('usuarios')
            ->has('acciones')
            ->has('filtros')
        );
    }

    public function test_a_gerente_with_the_seeded_permission_can_view_the_audit_log(): void
    {
        // Arrange
        $gerente = $this->usuarioConRol(Rol::Gerente);

        // Act
        $response = $this->actingAs($gerente)->get('/auditoria');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page->component('Auditoria/Index'));
    }
}
