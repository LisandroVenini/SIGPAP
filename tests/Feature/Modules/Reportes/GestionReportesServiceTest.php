<?php

namespace Tests\Feature\Modules\Reportes;

use App\Modules\Compras\Services\GestionOrdenesDeCompraService;
use App\Modules\Inventario\Services\ConsultaMovimientosService;
use App\Modules\Produccion\Services\GestionOrdenesDeProduccionService;
use App\Modules\Reportes\Services\GestionReportesService;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\UsuariosService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GestionReportesServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_report_over_the_row_limit_is_rejected(): void
    {
        // Arrange
        $this->mock(UsuariosService::class, function ($mock): void {
            $mock->shouldReceive('autorizar')->andReturn(true);
        });

        $this->mock(GestionOrdenesDeProduccionService::class, function ($mock): void {
            $mock->shouldReceive('reportePorFechaDeEntrega')
                ->once()
                ->andReturn(array_fill(0, GestionReportesService::LIMITE_FILAS + 1, []));
        });

        $this->mock(ConsultaMovimientosService::class);
        $this->mock(GestionOrdenesDeCompraService::class);

        $actor = Usuario::factory()->create();

        // Act / Assert
        try {
            app(GestionReportesService::class)->generar($actor, 'produccion', [
                'desde' => '2026-01-01',
                'hasta' => '2026-02-01',
            ]);

            $this->fail('A report over the row limit should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('exportar', $exception->errors());
        }
    }

    public function test_a_report_under_the_row_limit_is_generated(): void
    {
        // Arrange
        $this->mock(UsuariosService::class, function ($mock): void {
            $mock->shouldReceive('autorizar')->andReturn(true);
        });

        $this->mock(GestionOrdenesDeProduccionService::class, function ($mock): void {
            $mock->shouldReceive('reportePorFechaDeEntrega')
                ->once()
                ->andReturn([['orden_id' => 1]]);
        });

        $this->mock(ConsultaMovimientosService::class);
        $this->mock(GestionOrdenesDeCompraService::class);

        $actor = Usuario::factory()->create();

        // Act
        $reporte = app(GestionReportesService::class)->generar($actor, 'produccion', [
            'desde' => '2026-01-01',
            'hasta' => '2026-02-01',
        ]);

        // Assert
        $this->assertSame(1, $reporte['total']);
        $this->assertSame('Producción', $reporte['titulo']);
    }
}
