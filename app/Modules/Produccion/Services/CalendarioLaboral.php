<?php

namespace App\Modules\Produccion\Services;

use App\Modules\Produccion\Models\Feriado;
use App\Modules\Usuarios\Services\ParametroGlobalService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Working calendar of the plant.
 *
 * Configuration lives in `prod_calendario_laboral`: a JSON map of ISO weekday
 * (1 = Monday … 7 = Sunday) to a list of `[HH:MM, HH:MM]` intervals. Holidays
 * come from the synced `feriados` table; which types block work is configurable
 * because "feriado" and "no laborable" are not the same for a private plant.
 *
 * All arithmetic is done in working minutes, never in calendar days.
 */
final class CalendarioLaboral
{
    /**
     * Mon–Fri 08–12 and 16–20, Sat 08–12, Sun closed. Used only when the
     * parameter is missing or invalid, and reported as not configured.
     *
     * @var array<int, array<int, array{0:int, 1:int}>>
     */
    private const POR_DEFECTO = [
        1 => [[480, 720], [960, 1200]],
        2 => [[480, 720], [960, 1200]],
        3 => [[480, 720], [960, 1200]],
        4 => [[480, 720], [960, 1200]],
        5 => [[480, 720], [960, 1200]],
        6 => [[480, 720]],
        7 => [],
    ];

    /** @var array<int, array<int, array{0:int, 1:int}>>|null */
    private ?array $intervalos = null;

    /** @var array<string, string>|null */
    private ?array $feriados = null;

    /** @var array<int, string>|null */
    private ?array $tiposBloqueantes = null;

    private bool $configurado = false;

    public function __construct(private readonly ParametroGlobalService $parametros) {}

    /**
     * Whether a real calendar is configured (false means the fallback is used).
     */
    public function configurado(): bool
    {
        $this->intervalos();

        return $this->configurado;
    }

    /**
     * @return array<int, array{0:int, 1:int}>
     */
    public function intervalosDelDia(CarbonInterface $fecha): array
    {
        if (! $this->esHabil($fecha)) {
            return [];
        }

        return $this->intervalos()[$fecha->dayOfWeekIso] ?? [];
    }

    public function esHabil(CarbonInterface $fecha): bool
    {
        if (($this->intervalos()[$fecha->dayOfWeekIso] ?? []) === []) {
            return false;
        }

        $tipo = $this->feriados()[$fecha->toDateString()] ?? null;

        return $tipo === null || ! in_array($tipo, $this->tiposBloqueantes(), true);
    }

    public function minutosDelDia(CarbonInterface $fecha): int
    {
        return array_sum(array_map(
            fn (array $intervalo): int => $intervalo[1] - $intervalo[0],
            $this->intervalosDelDia($fecha),
        ));
    }

    /**
     * The next working instant at or after the given moment.
     */
    public function siguienteInicio(CarbonInterface $desde): CarbonImmutable
    {
        $cursor = CarbonImmutable::instance($desde);
        $dia = CarbonImmutable::parse($desde->toDateString());

        return $this->buscarSiguienteInicio($cursor, $dia);
    }

    /**
     * Consume working minutes from a moment and return the exact end.
     */
    public function avanzar(CarbonInterface $desde, float $minutos): CarbonImmutable
    {
        $segmentos = $this->segmentos($desde, $minutos);

        if ($segmentos === []) {
            return $this->siguienteInicio($desde);
        }

        return $segmentos[array_key_last($segmentos)]['fin'];
    }

    /**
     * Split a workload into contiguous working segments, so the Gantt can draw
     * one block per actual working run instead of a bar that pretends to work
     * through the night or the weekend.
     *
     * @return array<int, array{inicio: CarbonImmutable, fin: CarbonImmutable}>
     */
    public function segmentos(CarbonInterface $desde, float $minutos): array
    {
        $pendiente = (int) ceil($minutos);

        if ($pendiente <= 0) {
            return [];
        }

        $cursor = CarbonImmutable::instance($desde);
        $dia = CarbonImmutable::parse($desde->toDateString());
        $limite = $dia->addYears(5);
        $segmentos = [];

        while ($dia->lessThanOrEqualTo($limite) && $pendiente > 0) {
            foreach ($this->intervalosDelDia($dia) as [$inicio, $fin]) {
                $inicioReal = $dia->addMinutes($inicio);
                $finReal = $dia->addMinutes($fin);

                if ($cursor->greaterThanOrEqualTo($finReal)) {
                    continue;
                }

                if ($cursor->lessThan($inicioReal)) {
                    $cursor = $inicioReal;
                }

                $disponible = (int) (($finReal->getTimestamp() - $cursor->getTimestamp()) / 60);
                $consumir = min($disponible, $pendiente);
                $finSegmento = $cursor->addMinutes($consumir);

                $segmentos[] = ['inicio' => $cursor, 'fin' => $finSegmento];
                $cursor = $finSegmento;
                $pendiente -= $consumir;

                if ($pendiente <= 0) {
                    break;
                }
            }

            if ($pendiente > 0) {
                $dia = $dia->addDay();
                $cursor = $dia;
            }
        }

        return $segmentos;
    }

    /**
     * Working minutes inside the [desde, hasta] range.
     */
    public function minutosHabilesEntre(CarbonInterface $desde, CarbonInterface $hasta): int
    {
        if ($hasta->lessThanOrEqualTo($desde)) {
            return 0;
        }

        $total = 0;
        $dia = CarbonImmutable::parse($desde->toDateString());
        $ultimo = CarbonImmutable::parse($hasta->toDateString());

        while ($dia->lessThanOrEqualTo($ultimo)) {
            foreach ($this->intervalosDelDia($dia) as [$inicio, $fin]) {
                $inicioReal = $dia->addMinutes($inicio);
                $finReal = $dia->addMinutes($fin);
                $desdeReal = $inicioReal->greaterThan($desde) ? $inicioReal : $desde;
                $hastaReal = $finReal->lessThan($hasta) ? $finReal : $hasta;

                if ($hastaReal->greaterThan($desdeReal)) {
                    $total += (int) (($hastaReal->getTimestamp() - $desdeReal->getTimestamp()) / 60);
                }
            }

            $dia = $dia->addDay();
        }

        return $total;
    }

    private function buscarSiguienteInicio(CarbonImmutable $cursor, CarbonImmutable $dia): CarbonImmutable
    {
        $limite = $dia->addYears(5);

        while ($dia->lessThanOrEqualTo($limite)) {
            foreach ($this->intervalosDelDia($dia) as [$inicio, $fin]) {
                $inicioReal = $dia->addMinutes($inicio);
                $finReal = $dia->addMinutes($fin);

                if ($cursor->lessThan($inicioReal)) {
                    return $inicioReal;
                }

                if ($cursor->lessThan($finReal)) {
                    return $cursor;
                }
            }

            $dia = $dia->addDay();
            $cursor = $dia;
        }

        return $cursor;
    }

    /**
     * @return array<int, array<int, array{0:int, 1:int}>>
     */
    private function intervalos(): array
    {
        if ($this->intervalos !== null) {
            return $this->intervalos;
        }

        $crudo = $this->parametros->parametro('prod_calendario_laboral');
        $interpretado = $crudo !== null ? $this->interpretar($crudo) : null;

        if ($interpretado === null) {
            $this->intervalos = self::POR_DEFECTO;

            return $this->intervalos;
        }

        $this->configurado = true;
        $this->intervalos = $interpretado;

        return $this->intervalos;
    }

    /**
     * @return array<int, array<int, array{0:int, 1:int}>>|null
     */
    private function interpretar(string $crudo): ?array
    {
        $datos = json_decode($crudo, true);

        if (! is_array($datos)) {
            return null;
        }

        $resultado = [];

        foreach (range(1, 7) as $dia) {
            $intervalos = $datos[(string) $dia] ?? [];

            if (! is_array($intervalos)) {
                return null;
            }

            $delDia = [];

            foreach ($intervalos as $intervalo) {
                if (! is_array($intervalo) || count($intervalo) !== 2) {
                    return null;
                }

                $inicio = $this->minutos((string) $intervalo[0]);
                $fin = $this->minutos((string) $intervalo[1]);

                if ($inicio === null || $fin === null || $fin <= $inicio) {
                    return null;
                }

                $delDia[] = [$inicio, $fin];
            }

            usort($delDia, fn (array $a, array $b): int => $a[0] <=> $b[0]);
            $resultado[$dia] = $delDia;
        }

        return $resultado;
    }

    private function minutos(string $hora): ?int
    {
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $hora, $coincidencia) !== 1) {
            return null;
        }

        $horas = (int) $coincidencia[1];
        $minutos = (int) $coincidencia[2];

        if ($horas > 23 || $minutos > 59) {
            return null;
        }

        return ($horas * 60) + $minutos;
    }

    /**
     * @return array<string, string>
     */
    private function feriados(): array
    {
        if ($this->feriados !== null) {
            return $this->feriados;
        }

        $this->feriados = Feriado::query()
            ->get(['fecha', 'tipo'])
            ->mapWithKeys(fn (Feriado $feriado): array => [$feriado->fecha->toDateString() => $feriado->tipo])
            ->all();

        return $this->feriados;
    }

    /**
     * @return array<int, string>
     */
    private function tiposBloqueantes(): array
    {
        if ($this->tiposBloqueantes !== null) {
            return $this->tiposBloqueantes;
        }

        $crudo = $this->parametros->parametro('prod_feriados_tipos_bloqueantes');
        $tipos = $crudo !== null ? json_decode($crudo, true) : null;

        $this->tiposBloqueantes = is_array($tipos) && $tipos !== []
            ? array_values(array_filter($tipos, 'is_string'))
            : ['inamovible', 'trasladable', 'especial', 'turistico'];

        return $this->tiposBloqueantes;
    }
}
