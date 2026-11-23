<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Produccion\Models\Feriado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SincronizarFeriadosTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, array{fecha: string, nombre: string, tipo: string}>  $feriados
     * @return array<string, mixed>
     */
    private function payload(array $feriados): array
    {
        return [
            'mainEntity' => [
                'itemListElement' => array_map(
                    fn (array $feriado): array => [
                        'item' => [
                            'name' => $feriado['nombre'],
                            'additionalProperty' => ['value' => $feriado['tipo']],
                            'startDate' => $feriado['fecha'],
                        ],
                    ],
                    $feriados,
                ),
            ],
        ];
    }

    /**
     * @return array<int, array{fecha: string, nombre: string, tipo: string}>
     */
    private function feriadosDe(int $anio, int $cantidad = 12): array
    {
        $feriados = [];

        for ($dia = 1; $dia <= $cantidad; $dia++) {
            $feriados[] = [
                'fecha' => sprintf('%d-01-%02d', $anio, $dia),
                'nombre' => "Feriado {$dia}",
                'tipo' => 'inamovible',
            ];
        }

        return $feriados;
    }

    public function test_it_syncs_the_official_holidays(): void
    {
        Http::fake([
            'www.argentina.gob.ar/*' => Http::response($this->payload($this->feriadosDe(2026))),
        ]);

        $this->artisan('feriados:sincronizar', ['anio' => [2026]])
            ->assertSuccessful();

        $this->assertSame(12, Feriado::query()->count());

        $feriado = Feriado::query()->whereDate('fecha', '2026-01-01')->firstOrFail();
        $this->assertSame('Feriado 1', $feriado->nombre);
        $this->assertSame('inamovible', $feriado->tipo);
        $this->assertSame('datos.gob.ar', $feriado->fuente);
        $this->assertNotNull($feriado->sincronizado_en);
    }

    public function test_it_updates_a_holiday_that_changed_upstream(): void
    {
        Feriado::query()->create([
            'fecha' => '2026-01-01',
            'nombre' => 'Nombre viejo',
            'tipo' => 'trasladable',
            'fuente' => 'datos.gob.ar',
        ]);

        Http::fake([
            'www.argentina.gob.ar/*' => Http::response($this->payload($this->feriadosDe(2026))),
        ]);

        $this->artisan('feriados:sincronizar', ['anio' => [2026]])->assertSuccessful();

        $feriado = Feriado::query()->whereDate('fecha', '2026-01-01')->firstOrFail();
        $this->assertSame('Feriado 1', $feriado->nombre);
        $this->assertSame('inamovible', $feriado->tipo);
        $this->assertSame(12, Feriado::query()->count());
    }

    public function test_it_removes_a_synced_holiday_that_disappeared_upstream(): void
    {
        Http::fake([
            'www.argentina.gob.ar/*' => Http::response($this->payload($this->feriadosDe(2026))),
        ]);

        $this->artisan('feriados:sincronizar', ['anio' => [2026]])->assertSuccessful();

        Feriado::query()->create([
            'fecha' => '2026-12-25',
            'nombre' => 'Feriado obsoleto',
            'tipo' => 'inamovible',
            'fuente' => 'datos.gob.ar',
        ]);

        $this->artisan('feriados:sincronizar', ['anio' => [2026]])->assertSuccessful();

        $this->assertDatabaseMissing('feriados', ['fecha' => '2026-12-25']);
    }

    public function test_it_keeps_manual_entries_from_another_source(): void
    {
        Http::fake([
            'www.argentina.gob.ar/*' => Http::response($this->payload($this->feriadosDe(2026))),
        ]);

        Feriado::query()->create([
            'fecha' => '2026-12-31',
            'nombre' => 'Cierre de planta',
            'tipo' => 'manual',
            'fuente' => 'manual',
        ]);

        $this->artisan('feriados:sincronizar', ['anio' => [2026]])->assertSuccessful();

        $this->assertDatabaseHas('feriados', ['fecha' => '2026-12-31', 'fuente' => 'manual']);
    }

    public function test_it_does_not_touch_the_database_on_an_implausible_response(): void
    {
        Feriado::query()->create([
            'fecha' => '2026-01-01',
            'nombre' => 'Existente',
            'tipo' => 'inamovible',
            'fuente' => 'datos.gob.ar',
        ]);

        Http::fake([
            'www.argentina.gob.ar/*' => Http::response($this->payload($this->feriadosDe(2026, 3))),
        ]);

        $this->artisan('feriados:sincronizar', ['anio' => [2026]])
            ->assertFailed();

        $this->assertSame(1, Feriado::query()->count());
    }

    public function test_it_does_not_touch_the_database_when_the_request_fails(): void
    {
        Feriado::query()->create([
            'fecha' => '2026-01-01',
            'nombre' => 'Existente',
            'tipo' => 'inamovible',
            'fuente' => 'datos.gob.ar',
        ]);

        Http::fake([
            'www.argentina.gob.ar/*' => Http::response('', 503),
        ]);

        $this->artisan('feriados:sincronizar', ['anio' => [2026]])
            ->assertFailed();

        $this->assertSame(1, Feriado::query()->count());
    }

    public function test_a_year_not_published_yet_is_not_a_failure(): void
    {
        Http::fake([
            'www.argentina.gob.ar/*' => Http::response('', 404),
        ]);

        $this->artisan('feriados:sincronizar', ['anio' => [2027]])
            ->assertSuccessful();

        $this->assertSame(0, Feriado::query()->count());
    }

    public function test_it_syncs_the_current_and_next_year_by_default(): void
    {
        Http::fake(function (Request $request) {
            preg_match('/holidays-(\d{4})-es\.json/', $request->url(), $coincidencia);
            $anio = (int) ($coincidencia[1] ?? now()->year);

            return Http::response($this->payload($this->feriadosDe($anio)));
        });

        $this->artisan('feriados:sincronizar')->assertSuccessful();

        Http::assertSentCount(2);
        $this->assertSame(24, Feriado::query()->count());
    }
}
