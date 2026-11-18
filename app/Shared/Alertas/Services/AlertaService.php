<?php

namespace App\Shared\Alertas\Services;

use App\Modules\Usuarios\Models\Usuario;
use App\Shared\Alertas\Catalogo\CatalogoDeAlertas;
use App\Shared\Alertas\Enums\OrigenAlerta;
use App\Shared\Alertas\Enums\SeveridadAlerta;
use App\Shared\Alertas\Models\Alerta;
use App\Shared\Alertas\Models\AlertaLectura;
use App\Shared\Alertas\Models\ReglaDeAlerta;
use App\Shared\Alertas\Queries\AlertaQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AlertaService
{
    public function __construct(private readonly AlertaQuery $query) {}

    /**
     * Persist a transversal alert without a rule. Kept for the legacy contract and tests.
     *
     * @param  array{descripcion: string, orden_de_produccion_id?: int|null, articulo_id?: int|null, maquinaria_id?: int|null}  $contexto
     */
    public function emitirAlerta(string $tipo, array $contexto, string $origen): Alerta
    {
        return Alerta::create([
            'tipo' => $tipo,
            'severidad' => SeveridadAlerta::Info->value,
            'origen_tipo' => OrigenAlerta::Condicion->value,
            'fecha' => now(),
            'descripcion' => $contexto['descripcion'],
            'origen' => $origen,
            ...$this->referenciasDe($contexto),
        ]);
    }

    /**
     * Emit one alert per active rule of the given type.
     *
     * @param  array<string, mixed>  $contexto
     */
    public function emitirPorTipo(string $tipo, array $contexto, string $referencia): int
    {
        $reglas = ReglaDeAlerta::query()
            ->where('tipo', $tipo)
            ->where('activa', true)
            ->with('roles')
            ->get();

        $emitidas = 0;

        foreach ($reglas as $regla) {
            if (! $this->cumpleCondiciones($regla, $contexto)) {
                $this->resolver($regla, $referencia);

                continue;
            }

            $this->emitir($regla, $contexto, $referencia);
            $emitidas++;
        }

        return $emitidas;
    }

    /**
     * Emit (or refresh) the open alert of a rule for one reference.
     *
     * @param  array<string, mixed>  $contexto
     */
    public function emitir(ReglaDeAlerta $regla, array $contexto, string $referencia): Alerta
    {
        $clave = $this->claveDe($regla, $referencia);
        $descripcion = $this->renderizar($regla->mensaje, $contexto);
        $referencias = $this->referenciasDe($contexto);

        return DB::transaction(function () use ($regla, $contexto, $clave, $descripcion, $referencias): Alerta {
            DB::select('select pg_advisory_xact_lock(hashtext(?))', ['alerta_'.$clave]);

            $existente = Alerta::query()
                ->where('clave_dedupe', $clave)
                ->whereNull('resuelta_en')
                ->first();

            if ($existente !== null) {
                $existente->update([
                    'severidad' => $regla->severidad,
                    'descripcion' => $descripcion,
                    'contexto' => $contexto,
                    ...$referencias,
                ]);

                return $existente;
            }

            $alerta = Alerta::create([
                'regla_id' => $regla->id,
                'tipo' => $regla->tipo,
                'severidad' => $regla->severidad,
                'origen_tipo' => $regla->origen_tipo,
                'fecha' => now(),
                'descripcion' => $descripcion,
                'contexto' => $contexto,
                'clave_dedupe' => $clave,
                ...$referencias,
            ]);

            $alerta->roles()->sync($regla->roles()->pluck('roles.id'));

            return $alerta;
        });
    }

    /**
     * Emit the conditions that match and close the open alerts whose reference disappeared.
     *
     * @param  array<string, array<string, mixed>>  $contextosPorReferencia
     */
    public function sincronizarPorTipo(string $tipo, array $contextosPorReferencia): int
    {
        $emitidas = 0;

        foreach ($contextosPorReferencia as $referencia => $contexto) {
            $emitidas += $this->emitirPorTipo($tipo, $contexto, (string) $referencia);
        }

        $vigentes = array_keys($contextosPorReferencia);

        Alerta::query()
            ->where('tipo', $tipo)
            ->whereNull('resuelta_en')
            ->get()
            ->each(function (Alerta $alerta) use ($vigentes): void {
                $partes = explode(':', (string) $alerta->clave_dedupe);
                $referencia = implode(':', array_slice($partes, 2));

                if (! in_array($referencia, $vigentes, true)) {
                    $alerta->update(['resuelta_en' => now()]);
                }
            });

        return $emitidas;
    }

    /**
     * Close the open alerts of one rule for a reference that no longer matches.
     */
    public function resolver(ReglaDeAlerta $regla, string $referencia): int
    {
        return Alerta::query()
            ->where('regla_id', $regla->id)
            ->where('clave_dedupe', $this->claveDe($regla, $referencia))
            ->whereNull('resuelta_en')
            ->update(['resuelta_en' => now()]);
    }

    /**
     * Close every open alert of a type for a reference.
     */
    public function resolverPorTipo(string $tipo, string $referencia): int
    {
        $cerradas = 0;

        foreach (ReglaDeAlerta::query()->where('tipo', $tipo)->get() as $regla) {
            $cerradas += $this->resolver($regla, $referencia);
        }

        return $cerradas;
    }

    public function marcarLeida(Usuario $usuario, Alerta $alerta): void
    {
        AlertaLectura::query()->updateOrCreate(
            ['alerta_id' => $alerta->id, 'usuario_id' => $usuario->id],
            ['leida_en' => now()],
        );
    }

    public function marcarTodasLeidas(Usuario $usuario): int
    {
        $ids = $this->query->visiblesPara($usuario)
            ->whereDoesntHave('lecturas', fn (Builder $lecturas) => $lecturas->where('usuario_id', $usuario->id))
            ->pluck('id');

        if ($ids->isEmpty()) {
            return 0;
        }

        $filas = $ids->map(fn (int $id): array => [
            'alerta_id' => $id,
            'usuario_id' => $usuario->id,
            'leida_en' => now(),
        ])->all();

        AlertaLectura::query()->insertOrIgnore($filas);

        return $ids->count();
    }

    /**
     * Archive alerts older than the retention window.
     */
    public function archivarAntiguas(int $dias = 90): int
    {
        return Alerta::query()
            ->where('fecha', '<', now()->subDays($dias))
            ->whereNull('archivada_en')
            ->update(['archivada_en' => now()]);
    }

    /**
     * Replace the template variables with the context values.
     *
     * @param  array<string, mixed>  $contexto
     */
    public function renderizar(string $plantilla, array $contexto): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*([a-z_]+)\s*\}\}/',
            fn (array $coincidencia): string => (string) ($contexto[$coincidencia[1]] ?? '—'),
            $plantilla,
        );
    }

    /**
     * @param  array<string, mixed>  $contexto
     */
    private function cumpleCondiciones(ReglaDeAlerta $regla, array $contexto): bool
    {
        $tipo = CatalogoDeAlertas::obtener($regla->tipo);

        if ($tipo === null) {
            return false;
        }

        foreach ($tipo->condiciones as $condicion) {
            $clave = $condicion['clave'];
            $variable = $condicion['variable'] ?? $clave;

            if (! array_key_exists($variable, $contexto) || ! is_numeric($contexto[$variable])) {
                return false;
            }

            $valor = (float) $contexto[$variable];
            $limite = (float) ($regla->condiciones[$clave] ?? $condicion['por_defecto']);

            $cumple = match ($condicion['operador']) {
                'lt' => $valor < $limite,
                'lte' => $valor <= $limite,
                'gt' => $valor > $limite,
                'gte' => $valor >= $limite,
                default => false,
            };

            if (! $cumple) {
                return false;
            }
        }

        return true;
    }

    private function claveDe(ReglaDeAlerta $regla, string $referencia): string
    {
        return $regla->id.':'.$regla->tipo.':'.$referencia;
    }

    /**
     * @param  array<string, mixed>  $contexto
     * @return array<string, int>
     */
    private function referenciasDe(array $contexto): array
    {
        $columnas = [
            'orden_de_produccion_id',
            'articulo_id',
            'maquinaria_id',
            'orden_de_compra_id',
            'solicitud_de_cotizacion_id',
        ];

        $referencias = [];

        foreach ($columnas as $columna) {
            if (isset($contexto[$columna])) {
                $referencias[$columna] = (int) $contexto[$columna];
            }
        }

        return $referencias;
    }
}
