<?php

namespace Tests\Feature\Shared\Alertas;

use App\Modules\Inventario\Models\Articulo;
use App\Shared\Alertas\Models\Alerta;
use Database\Factories\OrdenDeProduccionFactory;
use Database\Seeders\ReglaDeAlertaSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluarAlertasTest extends TestCase
{
    use RefreshDatabase;

    private function sembrarReglas(): void
    {
        $this->seed([RoleSeeder::class, ReglaDeAlertaSeeder::class]);
    }

    public function test_it_emits_and_resolves_a_low_stock_condition(): void
    {
        // Arrange
        $this->sembrarReglas();
        $articulo = Articulo::factory()->create([
            'stock_fisico' => 1,
            'stock_minimo' => 10,
            'estado' => 'Activo',
        ]);

        // Act
        $this->artisan('alertas:evaluar')->assertSuccessful();

        // Assert
        $alerta = Alerta::query()
            ->where('tipo', 'stock_bajo_minimo')
            ->where('articulo_id', $articulo->id)
            ->firstOrFail();
        $this->assertNull($alerta->resuelta_en);
        $this->assertStringContainsString('bajo el mínimo', $alerta->descripcion);

        // Act
        $articulo->update(['stock_fisico' => 20]);
        $this->artisan('alertas:evaluar')->assertSuccessful();

        // Assert
        $this->assertNotNull($alerta->refresh()->resuelta_en);
    }

    public function test_it_emits_an_overdue_production_order(): void
    {
        // Arrange
        $this->sembrarReglas();
        $orden = OrdenDeProduccionFactory::new()->create([
            'estado' => 'En Curso',
            'fecha_entrega' => today()->subDays(3),
            'cantidad_requerida' => 10,
        ]);

        // Act
        $this->artisan('alertas:evaluar')->assertSuccessful();

        // Assert
        $this->assertDatabaseHas('alertas', [
            'tipo' => 'orden_atrasada',
            'orden_de_produccion_id' => $orden->id,
        ]);
    }

    public function test_the_scheduler_registers_the_evaluation_command(): void
    {
        // Act
        $eventos = app(Schedule::class)->events();

        // Assert
        $this->assertTrue(collect($eventos)->contains(
            fn ($evento): bool => str_contains((string) $evento->command, 'alertas:evaluar'),
        ));
    }
}
