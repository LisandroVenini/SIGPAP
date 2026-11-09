<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Produccion\Enums\EstadoMaquinaria;
use App\Modules\Produccion\Models\Maquinaria;
use App\Modules\Produccion\Services\GestionMantenimientosService;
use Database\Factories\PlanDeMantenimientoFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMantenimientoServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): GestionMantenimientosService
    {
        return app(GestionMantenimientosService::class);
    }

    public function test_resumen_dashboard_counts_blocked_machines_and_maintenance_plans(): void
    {
        // Arrange
        $bloqueada = Maquinaria::factory()->create([
            'estado_operativo' => EstadoMaquinaria::Bloqueada->value,
            'desgaste_acumulado' => 300,
        ]);
        $segundaBloqueada = Maquinaria::factory()->create([
            'estado_operativo' => EstadoMaquinaria::Bloqueada->value,
            'desgaste_acumulado' => 150,
        ]);
        Maquinaria::factory()->create([
            'estado_operativo' => EstadoMaquinaria::Disponible->value,
            'desgaste_acumulado' => 999,
        ]);

        PlanDeMantenimientoFactory::new()->create(['maquinaria_id' => $bloqueada->id]);
        PlanDeMantenimientoFactory::new()->create(['maquinaria_id' => $segundaBloqueada->id]);

        // Act
        $resumen = $this->service()->resumenDashboard();

        // Assert
        $this->assertSame([
            'maquinas_bloqueadas' => 2,
            'planes' => 2,
        ], $resumen);
    }

    public function test_maquinas_bloqueadas_lists_by_wear_descending_and_respects_the_limit(): void
    {
        // Arrange
        $alta = Maquinaria::factory()->create([
            'nombre' => 'Lijadora alta',
            'estado_operativo' => EstadoMaquinaria::Bloqueada->value,
            'desgaste_acumulado' => 300,
        ]);
        $media = Maquinaria::factory()->create([
            'nombre' => 'Lijadora media',
            'estado_operativo' => EstadoMaquinaria::Bloqueada->value,
            'desgaste_acumulado' => 150,
        ]);
        $baja = Maquinaria::factory()->create([
            'nombre' => 'Lijadora baja',
            'estado_operativo' => EstadoMaquinaria::Bloqueada->value,
            'desgaste_acumulado' => 20,
        ]);
        Maquinaria::factory()->create([
            'estado_operativo' => EstadoMaquinaria::Disponible->value,
            'desgaste_acumulado' => 999,
        ]);

        // Act
        $listado = $this->service()->maquinasBloqueadas(2);

        // Assert
        $this->assertSame([
            ['id' => $alta->id, 'nombre' => 'Lijadora alta', 'desgaste' => 300.0],
            ['id' => $media->id, 'nombre' => 'Lijadora media', 'desgaste' => 150.0],
        ], $listado);
        $this->assertSame($baja->id, $this->service()->maquinasBloqueadas(10)[2]['id']);
    }

    public function test_resumen_dashboard_returns_zeros_and_an_empty_list_without_maintenance_data(): void
    {
        // Act
        $resumen = $this->service()->resumenDashboard();
        $listado = $this->service()->maquinasBloqueadas(5);

        // Assert
        $this->assertSame([
            'maquinas_bloqueadas' => 0,
            'planes' => 0,
        ], $resumen);
        $this->assertSame([], $listado);
    }
}
