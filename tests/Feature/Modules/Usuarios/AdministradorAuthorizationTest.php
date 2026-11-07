<?php

namespace Tests\Feature\Modules\Usuarios;

use App\Modules\Produccion\Models\Etapa;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AdministradorAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioConRol(?Rol $rol = null): Usuario
    {
        $this->seed(RoleSeeder::class);

        $usuario = Usuario::factory()->create();

        if ($rol !== null) {
            $usuario->assignRole($rol->value);
        }

        return $usuario;
    }

    public function test_gate_evaluation_without_an_authenticated_user_does_not_type_error(): void
    {
        // Arrange
        $this->assertGuest();

        // Act
        $habilidadSuelta = Gate::allows('produccion.gestionar');
        $politica = Gate::forUser(null)->allows('viewAny', Etapa::class);

        // Assert
        $this->assertFalse($habilidadSuelta);
        $this->assertFalse($politica);
    }

    public function test_administrador_passes_every_ability_without_seeded_permissions(): void
    {
        // Arrange — only roles are seeded, no permission is granted to the admin.
        $admin = $this->usuarioConRol(Rol::Administrador);

        // Act / Assert
        $this->assertTrue($admin->can('produccion.gestionar'));
        $this->assertTrue($admin->can('inventario.gestionar'));
        $this->assertTrue($admin->can('usuarios.gestionar'));
        $this->assertTrue($admin->can('habilidad.que.no.existe'));
    }

    public function test_administrador_reaches_the_hoja_de_ruta_screen(): void
    {
        // Arrange
        $admin = $this->usuarioConRol(Rol::Administrador);

        // Act
        $response = $this->actingAs($admin)->get('/produccion/hoja-de-ruta');

        // Assert
        $response->assertOk();
    }

    public function test_administrador_reaches_etapas_without_the_permission_seeder(): void
    {
        // Arrange
        $admin = $this->usuarioConRol(Rol::Administrador);

        // Act
        $response = $this->actingAs($admin)->get('/produccion/etapas');

        // Assert
        $this->assertDatabaseMissing('permissions', ['name' => 'produccion.gestionar']);
        $response->assertOk();
    }

    public function test_administrador_reaches_the_articulos_screen(): void
    {
        // Arrange
        $admin = $this->usuarioConRol(Rol::Administrador);

        // Act
        $response = $this->actingAs($admin)->get('/inventario/articulos');

        // Assert
        $response->assertOk();
    }

    public function test_supervisor_without_the_permission_seeder_is_still_forbidden(): void
    {
        // Arrange
        $supervisor = $this->usuarioConRol(Rol::SupervisorProduccion);

        // Act
        $puede = $supervisor->can('produccion.gestionar');
        $response = $this->actingAs($supervisor)->get('/produccion/etapas');

        // Assert
        $this->assertFalse($puede);
        $response->assertForbidden();
    }

    public function test_usuario_without_role_keeps_no_access(): void
    {
        // Arrange
        $usuario = $this->usuarioConRol();

        // Act
        $puede = $usuario->can('produccion.gestionar');
        $response = $this->actingAs($usuario)->get('/produccion/hoja-de-ruta');

        // Assert
        $this->assertFalse($puede);
        $response->assertForbidden();
    }

    public function test_administrador_cannot_deactivate_self_even_with_gate_bypass(): void
    {
        // Arrange
        $admin = $this->usuarioConRol(Rol::Administrador);

        // Act
        $response = $this->actingAs($admin)->patch("/usuarios/{$admin->id}/estado");

        // Assert
        $this->assertTrue(Gate::forUser($admin)->allows('cambiarEstado', $admin));
        $response->assertSessionHasErrors('estado');
        $this->assertSame('Activo', $admin->fresh()->estado);
    }

    public function test_administrador_cannot_remove_own_administrador_role_even_with_gate_bypass(): void
    {
        // Arrange
        $admin = $this->usuarioConRol(Rol::Administrador);

        // Act
        $response = $this->actingAs($admin)->put("/usuarios/{$admin->id}", [
            'nombre' => $admin->nombre,
            'email' => $admin->email,
            'rol' => Rol::Gerente->value,
            'estado' => 'Activo',
        ]);

        // Assert
        $this->assertTrue(Gate::forUser($admin)->allows('update', $admin));
        $response->assertSessionHasErrors('rol');
        $this->assertTrue($admin->fresh()->hasRole(Rol::Administrador->value));
    }
}
