<?php

namespace Tests\Feature\Modules\Produccion;

use App\Modules\Produccion\Models\Feriado;
use App\Modules\Produccion\Services\CalendarioLaboral;
use App\Modules\Usuarios\Models\ParametroGlobal;
use Carbon\CarbonImmutable;
use Database\Seeders\ParametroGlobalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarioLaboralTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ParametroGlobalSeeder::class);
    }

    private function calendario(): CalendarioLaboral
    {
        return app(CalendarioLaboral::class);
    }

    private function momento(string $fecha): CarbonImmutable
    {
        return CarbonImmutable::parse($fecha);
    }

    public function test_it_reports_the_working_minutes_of_each_day(): void
    {
        $calendario = $this->calendario();

        $this->assertTrue($calendario->configurado());
        $this->assertSame(480, $calendario->minutosDelDia($this->momento('2026-11-02'))); // lunes
        $this->assertSame(240, $calendario->minutosDelDia($this->momento('2026-11-07'))); // sábado
        $this->assertSame(0, $calendario->minutosDelDia($this->momento('2026-11-08')));   // domingo
        $this->assertFalse($calendario->esHabil($this->momento('2026-11-08')));
    }

    public function test_a_blocking_holiday_is_not_working(): void
    {
        Feriado::query()->create([
            'fecha' => '2026-11-03',
            'nombre' => 'Feriado',
            'tipo' => 'inamovible',
            'fuente' => 'datos.gob.ar',
        ]);

        $calendario = $this->calendario();

        $this->assertFalse($calendario->esHabil($this->momento('2026-11-03')));
        $this->assertSame(0, $calendario->minutosDelDia($this->momento('2026-11-03')));
    }

    public function test_a_non_blocking_holiday_type_still_works(): void
    {
        Feriado::query()->create([
            'fecha' => '2026-11-03',
            'nombre' => 'Día no laborable',
            'tipo' => 'no_laborable',
            'fuente' => 'datos.gob.ar',
        ]);

        $calendario = $this->calendario();

        $this->assertTrue($calendario->esHabil($this->momento('2026-11-03')));
        $this->assertSame(480, $calendario->minutosDelDia($this->momento('2026-11-03')));
    }

    public function test_it_advances_within_the_same_interval(): void
    {
        $fin = $this->calendario()->avanzar($this->momento('2026-11-02 09:00'), 120);

        $this->assertSame('2026-11-02 11:00:00', $fin->toDateTimeString());
    }

    public function test_it_skips_the_midday_break(): void
    {
        // 11:00 → 12:00 = 60 minutes, then 16:00 → 17:00.
        $fin = $this->calendario()->avanzar($this->momento('2026-11-02 11:00'), 120);

        $this->assertSame('2026-11-02 17:00:00', $fin->toDateTimeString());
    }

    public function test_it_skips_the_night(): void
    {
        // 19:00 → 20:00 = 60 minutes, then Tuesday 08:00 → 09:00.
        $fin = $this->calendario()->avanzar($this->momento('2026-11-02 19:00'), 120);

        $this->assertSame('2026-11-03 09:00:00', $fin->toDateTimeString());
    }

    public function test_it_skips_the_weekend(): void
    {
        // Saturday 11:00 → 12:00 = 60 minutes, Sunday closed, Monday 08:00 → 09:00.
        $fin = $this->calendario()->avanzar($this->momento('2026-11-07 11:00'), 120);

        $this->assertSame('2026-11-09 09:00:00', $fin->toDateTimeString());
    }

    public function test_it_skips_a_holiday(): void
    {
        Feriado::query()->create([
            'fecha' => '2026-11-02',
            'nombre' => 'Feriado puente',
            'tipo' => 'turistico',
            'fuente' => 'datos.gob.ar',
        ]);

        $fin = $this->calendario()->avanzar($this->momento('2026-11-02 09:00'), 60);

        $this->assertSame('2026-11-03 09:00:00', $fin->toDateTimeString());
    }

    public function test_segments_split_at_the_night(): void
    {
        $segmentos = $this->calendario()->segmentos($this->momento('2026-11-02 19:00'), 120);

        $this->assertCount(2, $segmentos);
        $this->assertSame('2026-11-02 19:00:00', $segmentos[0]['inicio']->toDateTimeString());
        $this->assertSame('2026-11-02 20:00:00', $segmentos[0]['fin']->toDateTimeString());
        $this->assertSame('2026-11-03 08:00:00', $segmentos[1]['inicio']->toDateTimeString());
        $this->assertSame('2026-11-03 09:00:00', $segmentos[1]['fin']->toDateTimeString());
    }

    public function test_it_counts_the_working_minutes_between_two_moments(): void
    {
        $minutos = $this->calendario()->minutosHabilesEntre(
            $this->momento('2026-11-02 11:00'),
            $this->momento('2026-11-03 09:00'),
        );

        // Monday 11:00 → 12:00 (60) + 16:00 → 20:00 (240) + Tuesday 08:00 → 09:00 (60).
        $this->assertSame(360, $minutos);
    }

    public function test_the_next_start_moves_to_the_next_working_window(): void
    {
        $inicio = $this->calendario()->siguienteInicio($this->momento('2026-11-08 12:00'));

        $this->assertSame('2026-11-09 08:00:00', $inicio->toDateTimeString());
    }

    public function test_an_instant_inside_a_window_is_its_own_next_start(): void
    {
        $inicio = $this->calendario()->siguienteInicio($this->momento('2026-11-02 10:30'));

        $this->assertSame('2026-11-02 10:30:00', $inicio->toDateTimeString());
    }

    public function test_it_falls_back_to_the_default_calendar_when_the_parameter_is_invalid(): void
    {
        ParametroGlobal::query()
            ->where('nombre', 'prod_calendario_laboral')
            ->update(['valor' => '{esto no es json}']);

        $calendario = $this->calendario();

        $this->assertFalse($calendario->configurado());
        $this->assertSame(480, $calendario->minutosDelDia($this->momento('2026-11-02')));
        $this->assertSame(0, $calendario->minutosDelDia($this->momento('2026-11-08')));
    }
}
