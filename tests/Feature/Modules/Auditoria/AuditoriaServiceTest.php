<?php

namespace Tests\Feature\Modules\Auditoria;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditoriaServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_registers_an_audit_event(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();
        $service = new AuditoriaService;

        // Act
        $evento = $service->registrarEvento(
            $usuario->id,
            'aprobar_oc',
            ['estado' => 'Emitida'],
            ['estado' => 'Confirmada'],
            '127.0.0.1',
            'App\\Modules\\Compras\\Models\\OrdenDeCompra',
            42,
        );

        // Assert
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'usuario_id' => $usuario->id,
            'accion' => 'aprobar_oc',
            'direccion_ip' => '127.0.0.1',
            'auditable_type' => 'App\\Modules\\Compras\\Models\\OrdenDeCompra',
            'auditable_id' => 42,
        ]);
        $this->assertSame(['estado' => 'Emitida'], $evento->estado_anterior);
        $this->assertSame(['estado' => 'Confirmada'], $evento->estado_nuevo);
    }

    public function test_it_registers_an_event_without_optional_data(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();
        $service = new AuditoriaService;

        // Act
        $evento = $service->registrarEvento($usuario->id, 'logout');

        // Assert
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'id' => $evento->id,
            'usuario_id' => $usuario->id,
            'accion' => 'logout',
        ]);
        $this->assertNull($evento->estado_anterior);
        $this->assertNull($evento->auditable_type);
    }
}
