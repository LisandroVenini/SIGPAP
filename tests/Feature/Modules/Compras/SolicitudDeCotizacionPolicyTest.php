<?php

namespace Tests\Feature\Modules\Compras;

use App\Modules\Compras\Models\SolicitudDeCotizacion;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Factories\SolicitudDeCotizacionFactory;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SolicitudDeCotizacionPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioConRol(?Rol $rol = null): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();

        if ($rol !== null) {
            $usuario->assignRole($rol->value);
        }

        return $usuario;
    }

    public function test_administrator_and_purchasing_manager_can_manage_solicitudes(): void
    {
        // Arrange
        $admin = $this->usuarioConRol(Rol::Administrador);
        $encargado = $this->usuarioConRol(Rol::EncargadoCompras);
        $solicitud = SolicitudDeCotizacionFactory::new()->create();

        // Act / Assert
        foreach ([$admin, $encargado] as $actor) {
            $this->assertTrue(Gate::forUser($actor)->allows('viewAny', SolicitudDeCotizacion::class));
            $this->assertTrue(Gate::forUser($actor)->allows('create', SolicitudDeCotizacion::class));
            $this->assertTrue(Gate::forUser($actor)->allows('update', $solicitud));
            $this->assertTrue(Gate::forUser($actor)->allows('delete', $solicitud));
        }
    }

    public function test_other_roles_cannot_manage_solicitudes(): void
    {
        // Arrange
        $gerente = $this->usuarioConRol(Rol::Gerente);
        $sinRol = $this->usuarioConRol();
        $solicitud = SolicitudDeCotizacionFactory::new()->create();

        // Act / Assert
        foreach ([$gerente, $sinRol] as $actor) {
            $this->assertFalse(Gate::forUser($actor)->allows('viewAny', SolicitudDeCotizacion::class));
            $this->assertFalse(Gate::forUser($actor)->allows('create', SolicitudDeCotizacion::class));
            $this->assertFalse(Gate::forUser($actor)->allows('update', $solicitud));
            $this->assertFalse(Gate::forUser($actor)->allows('delete', $solicitud));
        }
    }
}
