<?php

namespace Tests\Feature\Modules\Auditoria;

use App\Modules\Auditoria\Models\EventoDeAuditoria;
use App\Modules\Auditoria\Queries\EventosDeAuditoriaQuery;
use App\Modules\Usuarios\Models\Usuario;
use Database\Factories\EventoDeAuditoriaFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventosDeAuditoriaQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_listar_paginates_25_events_ordered_by_fecha_hora_and_id_descending(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();

        EventoDeAuditoriaFactory::new()->count(30)->create([
            'usuario_id' => $usuario->id,
            'fecha_hora' => '2026-01-10 09:00:00',
        ]);

        $ultimo = EventoDeAuditoria::query()->orderByDesc('id')->firstOrFail();

        // Act
        $eventos = (new EventosDeAuditoriaQuery)->listar([]);

        // Assert
        $this->assertSame(30, $eventos->total());
        $this->assertSame(25, $eventos->perPage());
        $this->assertCount(25, $eventos->items());
        $this->assertSame($ultimo->id, $eventos->items()[0]['id']);
        $this->assertSame(
            ['id', 'fecha_hora', 'usuario_id', 'accion', 'direccion_ip', 'auditable_type', 'auditable_id', 'estado_anterior', 'estado_nuevo'],
            array_keys($eventos->items()[0]),
        );
    }

    public function test_listar_filters_by_fecha_hora_range_inclusively(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();

        EventoDeAuditoriaFactory::new()->create(['usuario_id' => $usuario->id, 'fecha_hora' => '2026-01-04 23:59:59']);
        EventoDeAuditoriaFactory::new()->create(['usuario_id' => $usuario->id, 'fecha_hora' => '2026-01-05 00:00:00']);
        $dentro = EventoDeAuditoriaFactory::new()->create(['usuario_id' => $usuario->id, 'fecha_hora' => '2026-01-10 23:59:59']);
        EventoDeAuditoriaFactory::new()->create(['usuario_id' => $usuario->id, 'fecha_hora' => '2026-01-11 00:00:00']);

        // Act
        $eventos = (new EventosDeAuditoriaQuery)->listar([
            'fecha_desde' => '2026-01-05',
            'fecha_hasta' => '2026-01-10',
        ]);

        // Assert
        $this->assertSame(2, $eventos->total());
        $this->assertSame($dentro->id, $eventos->items()[0]['id']);
    }
}
