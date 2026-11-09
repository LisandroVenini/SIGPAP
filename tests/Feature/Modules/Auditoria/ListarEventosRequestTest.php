<?php

namespace Tests\Feature\Modules\Auditoria;

use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListarEventosRequestTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Usuario
    {
        $this->seed(RoleSeeder::class);

        $admin = Usuario::factory()->create();
        $admin->assignRole(Rol::Administrador->value);

        return $admin;
    }

    public function test_it_rejects_an_inverted_date_range(): void
    {
        // Arrange
        $admin = $this->admin();

        // Act
        $response = $this->actingAs($admin)->get('/auditoria?fecha_desde=2026-02-10&fecha_hasta=2026-02-01');

        // Assert
        $response->assertSessionHasErrors('fecha_hasta');
    }

    public function test_a_non_existent_usuario_id_does_not_break_the_query(): void
    {
        // Arrange
        $admin = $this->admin();

        // Act
        $response = $this->actingAs($admin)->get('/auditoria?usuario_id=999999');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Auditoria/Index')->has('eventos.data', 0));
    }

    public function test_a_non_integer_usuario_id_is_rejected(): void
    {
        // Arrange
        $admin = $this->admin();

        // Act
        $response = $this->actingAs($admin)->get('/auditoria?usuario_id=abc');

        // Assert
        $response->assertSessionHasErrors('usuario_id');
    }
}
