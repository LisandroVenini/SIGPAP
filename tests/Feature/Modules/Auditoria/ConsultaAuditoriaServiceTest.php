<?php

namespace Tests\Feature\Modules\Auditoria;

use App\Modules\Auditoria\Services\ConsultaAuditoriaService;
use App\Modules\Usuarios\Models\Usuario;
use Database\Factories\EventoDeAuditoriaFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConsultaAuditoriaServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_listar_filters_by_usuario(): void
    {
        // Arrange
        $laura = Usuario::factory()->create(['nombre' => 'Laura Cáceres']);
        $pedro = Usuario::factory()->create(['nombre' => 'Pedro Gómez']);

        EventoDeAuditoriaFactory::new()->create(['usuario_id' => $laura->id, 'accion' => 'crear_usuario']);
        EventoDeAuditoriaFactory::new()->create(['usuario_id' => $pedro->id, 'accion' => 'crear_usuario']);

        // Act
        $resultado = $this->app->make(ConsultaAuditoriaService::class)->listar(['usuario_id' => $laura->id]);

        // Assert
        $this->assertSame(1, $resultado['eventos']->total());
        $this->assertSame($laura->id, $resultado['eventos']->items()[0]['usuario_id']);
    }

    public function test_listar_filters_by_partial_accion_case_insensitively(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();

        EventoDeAuditoriaFactory::new()->create(['usuario_id' => $usuario->id, 'accion' => 'Crear_Usuario']);
        EventoDeAuditoriaFactory::new()->create(['usuario_id' => $usuario->id, 'accion' => 'aprobar_oc']);

        // Act
        $resultado = $this->app->make(ConsultaAuditoriaService::class)->listar(['accion' => 'crear']);

        // Assert
        $this->assertSame(1, $resultado['eventos']->total());
        $this->assertSame('Crear_Usuario', $resultado['eventos']->items()[0]['accion']);
    }

    public function test_listar_filters_by_partial_direccion_ip(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();

        EventoDeAuditoriaFactory::new()->create(['usuario_id' => $usuario->id, 'direccion_ip' => '192.168.0.10']);
        EventoDeAuditoriaFactory::new()->create(['usuario_id' => $usuario->id, 'direccion_ip' => '10.0.0.5']);

        // Act
        $resultado = $this->app->make(ConsultaAuditoriaService::class)->listar(['direccion_ip' => '168.0']);

        // Assert
        $this->assertSame(1, $resultado['eventos']->total());
        $this->assertSame('192.168.0.10', $resultado['eventos']->items()[0]['direccion_ip']);
    }

    public function test_listar_filters_by_date_range(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();

        EventoDeAuditoriaFactory::new()->create(['usuario_id' => $usuario->id, 'fecha_hora' => '2026-02-01 08:00:00']);
        EventoDeAuditoriaFactory::new()->create(['usuario_id' => $usuario->id, 'fecha_hora' => '2026-02-15 08:00:00']);
        EventoDeAuditoriaFactory::new()->create(['usuario_id' => $usuario->id, 'fecha_hora' => '2026-03-01 08:00:00']);

        // Act
        $resultado = $this->app->make(ConsultaAuditoriaService::class)->listar([
            'fecha_desde' => '2026-02-01',
            'fecha_hasta' => '2026-02-28',
        ]);

        // Assert
        $this->assertSame(2, $resultado['eventos']->total());
    }

    public function test_listar_resolves_usuario_names_in_a_single_batch_query(): void
    {
        // Arrange
        $usuarios = Usuario::factory()->count(5)->create();

        foreach ($usuarios as $usuario) {
            EventoDeAuditoriaFactory::new()->count(2)->create(['usuario_id' => $usuario->id]);
        }

        $service = $this->app->make(ConsultaAuditoriaService::class);

        DB::flushQueryLog();
        DB::enableQueryLog();

        // Act
        $resultado = $service->listar([]);

        // Assert
        $consultasAUsuarios = collect(DB::getQueryLog())
            ->filter(fn (array $entry): bool => str_contains($entry['query'], 'from "usuarios"'))
            ->count();

        DB::disableQueryLog();

        $this->assertSame(1, $consultasAUsuarios);
        $this->assertCount(5, $resultado['usuarios']);
        $this->assertSame(
            $usuarios->pluck('nombre', 'id')->sortKeys()->all(),
            collect($resultado['usuarios'])->pluck('nombre', 'id')->sortKeys()->all(),
        );
    }

    public function test_listar_returns_distinct_actions_ordered_alphabetically(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();

        EventoDeAuditoriaFactory::new()->create(['usuario_id' => $usuario->id, 'accion' => 'crear_usuario']);
        EventoDeAuditoriaFactory::new()->create(['usuario_id' => $usuario->id, 'accion' => 'aprobar_oc']);
        EventoDeAuditoriaFactory::new()->create(['usuario_id' => $usuario->id, 'accion' => 'crear_usuario']);

        // Act
        $resultado = $this->app->make(ConsultaAuditoriaService::class)->listar([]);

        // Assert
        $this->assertSame(['aprobar_oc', 'crear_usuario'], $resultado['acciones']);
    }
}
