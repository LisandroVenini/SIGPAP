<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Produccion\Database\Seeders\ProduccionSeeder;
use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use App\Modules\Produccion\Models\AsignacionDeEtapa;
use App\Modules\Produccion\Models\ControlDeCalidad;
use App\Modules\Produccion\Models\Etapa;
use App\Modules\Produccion\Models\Maquinaria;
use App\Modules\Produccion\Models\Operario;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Models\ParteDiario;
use App\Modules\Produccion\Models\PlanDeMantenimiento;
use App\Modules\Produccion\Models\PlanificacionDeProduccion;
use App\Modules\Produccion\Models\RegistroPrioridad;
use App\Modules\Produccion\Services\GestionCronogramaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProduccionSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, int>
     */
    private function conteos(): array
    {
        return [
            'etapas' => Etapa::withTrashed()->count(),
            'operarios' => Operario::withTrashed()->count(),
            'maquinarias' => Maquinaria::count(),
            'planificaciones' => PlanificacionDeProduccion::count(),
            'ordenes' => OrdenDeProduccion::count(),
            'asignaciones' => AsignacionDeEtapa::count(),
            'partes' => ParteDiario::count(),
            'controles' => ControlDeCalidad::count(),
            'planes' => PlanDeMantenimiento::count(),
            'prioridades' => RegistroPrioridad::count(),
        ];
    }

    public function test_seeds_every_state_with_consistent_data_and_is_idempotent(): void
    {
        // Act
        $this->seed(ProduccionSeeder::class);
        $primeraCorrida = $this->conteos();
        $this->seed(ProduccionSeeder::class);
        $segundaCorrida = $this->conteos();

        // Assert: running twice does not duplicate anything
        $this->assertSame($primeraCorrida, $segundaCorrida);
        $this->assertGreaterThanOrEqual(15, $primeraCorrida['ordenes']);
        $this->assertGreaterThan(0, $primeraCorrida['partes']);
        $this->assertGreaterThan(0, $primeraCorrida['controles']);
        $this->assertGreaterThan(0, $primeraCorrida['prioridades']);
        $this->assertSame(3, $primeraCorrida['planes']);

        // Assert: every order state is represented
        $estados = OrdenDeProduccion::query()->distinct()->pluck('estado')->all();
        $this->assertEqualsCanonicalizing(EstadoOrdenProduccion::values(), $estados);
    }

    public function test_seeds_soft_deleted_catalogs_and_keeps_them_out_of_the_active_lists(): void
    {
        // Arrange
        $this->seed(ProduccionSeeder::class);

        // Assert
        $this->assertTrue(Etapa::withTrashed()->where('nombre', 'Etapa obsoleta')->exists());
        $this->assertFalse(Etapa::query()->where('nombre', 'Etapa obsoleta')->exists());
        $this->assertTrue(Operario::withTrashed()->where('nombre', 'Franco')->exists());
        $this->assertFalse(Operario::query()->where('nombre', 'Franco')->exists());
    }

    public function test_seeds_daily_parts_and_quality_without_exceeding_the_requested_quantity(): void
    {
        // Arrange
        $this->seed(ProduccionSeeder::class);

        // Assert: both machine and machine-less parts exist, and there are rejections
        $this->assertTrue(ParteDiario::query()->whereNotNull('maquinaria_id')->exists());
        $this->assertTrue(ParteDiario::query()->whereNull('maquinaria_id')->exists());
        $this->assertTrue(ControlDeCalidad::query()->where('cantidad_rechazada', '>', 0)->exists());

        // Assert: no stage received more units than the order requested
        $excedidas = ParteDiario::query()
            ->selectRaw('orden_de_produccion_id, etapa_id, sum(cantidad_conformada) as total')
            ->groupBy('orden_de_produccion_id', 'etapa_id')
            ->get()
            ->filter(function (ParteDiario $fila): bool {
                $orden = OrdenDeProduccion::query()->find($fila->orden_de_produccion_id);

                return $orden !== null
                    && (float) $fila->total > (float) $orden->cantidad_requerida;
            });

        $this->assertCount(0, $excedidas);
    }

    public function test_seeds_closed_orders_and_at_least_one_delivery_conflict(): void
    {
        // Arrange
        $this->seed(ProduccionSeeder::class);

        // Assert: closed orders carry their produced quantity and end date
        $cerradas = OrdenDeProduccion::query()
            ->where('estado', EstadoOrdenProduccion::Cerrada->value)
            ->get();

        $this->assertCount(2, $cerradas);

        foreach ($cerradas as $orden) {
            $this->assertSame((float) $orden->cantidad_requerida, (float) $orden->cantidad_producida);
            $this->assertNotNull($orden->fecha_fin);
        }

        // Assert: the projection flags delivery conflicts
        $cronograma = app(GestionCronogramaService::class)->proyectar();

        $this->assertTrue(
            collect($cronograma['filas'])->contains(fn (array $fila): bool => $fila['conflicto_entrega']),
        );
    }
}
