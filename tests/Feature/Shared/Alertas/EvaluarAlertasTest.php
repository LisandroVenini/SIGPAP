<?php

namespace Tests\Feature\Shared\Alertas;

use App\Modules\Compras\Models\DesempenoProveedor;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Usuarios\Enums\Rol;
use App\Shared\Alertas\Models\Alerta;
use App\Shared\Alertas\Models\ReglaDeAlerta;
use Database\Factories\OrdenDeCompraFactory;
use Database\Factories\OrdenDeProduccionFactory;
use Database\Seeders\ReglaDeAlertaSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
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

    public function test_it_emits_a_critical_supplier_alert(): void
    {
        // Arrange
        $this->sembrarReglas();
        $proveedor = Proveedor::factory()->create(['razon_social' => 'Aceros del Sur', 'estado' => 'Activo']);
        $orden = OrdenDeCompraFactory::new()->create([
            'proveedor_id' => $proveedor->id,
            'estado' => 'Recibida',
        ]);
        DesempenoProveedor::create([
            'proveedor_id' => $proveedor->id,
            'orden_de_compra_id' => $orden->id,
            'tiempo_entrega_real' => 3,
            'fecha_registro' => today(),
        ]);

        $regla = ReglaDeAlerta::create([
            'nombre' => 'Proveedor crítico',
            'tipo' => 'proveedor_critico',
            'modulo' => 'Compras',
            'severidad' => 'Atencion',
            'origen_tipo' => 'condicion',
            'condiciones' => ['puntaje_minimo' => 100],
            'mensaje' => '{{proveedor}} tiene un puntaje de {{puntaje}}.',
            'activa' => true,
        ]);
        $regla->roles()->sync([Role::findByName(Rol::EncargadoCompras->value, 'web')->id]);

        // Act
        $this->artisan('alertas:evaluar')->assertSuccessful();

        // Assert
        $this->assertDatabaseHas('alertas', ['tipo' => 'proveedor_critico']);
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
