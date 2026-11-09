<?php

namespace App\Modules\Compras\Services;

use App\Modules\Compras\Models\Cotizacion;
use App\Modules\Compras\Models\LineaCotizacion;
use App\Modules\Compras\Models\LineaSolicitudCotizacion;
use App\Modules\Compras\Queries\InformeMulticriterioQuery;
use App\Modules\Usuarios\Services\ParametroGlobalService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class GestionInformeMulticriterioService
{
    private const PESO_PRECIO = 'compras_multicriterio_peso_precio';

    private const PESO_ENTREGA = 'compras_multicriterio_peso_entrega';

    private const PESO_CONFIABILIDAD = 'compras_multicriterio_peso_confiabilidad';

    private const CONFIABILIDAD_SIN_HISTORIAL = 'compras_multicriterio_confiabilidad_sin_historial';

    private const PENALIZAR_COBERTURA = 'compras_multicriterio_penalizar_cobertura';

    public function __construct(
        private readonly ParametroGlobalService $parametros,
        private readonly GestionRankingService $ranking,
        private readonly InformeMulticriterioQuery $query,
    ) {}

    /**
     * Multicriteria report for one solicitud.
     *
     * @return array{
     *     parametros: array{pesos: array<string, float>, sin_historial: float, penalizar_cobertura: bool},
     *     cotizaciones: array<int, array{cotizacion_id: int, proveedor: string, precio: float, dias: int, cobertura: float, vencida: bool, confiabilidad: float, precio_norm: float, entrega_norm: float, confiabilidad_norm: float, puntaje: float}>,
     *     recomendada_id: int|null
     * }
     */
    public function paraSolicitud(int $solicitudId): array
    {
        return $this->paraSolicitudes([$solicitudId])[$solicitudId];
    }

    /**
     * Multicriteria reports for several solicitudes, scoring every involved supplier once.
     *
     * @param  array<int, int>  $solicitudIds
     * @return array<int, array{
     *     parametros: array{pesos: array<string, float>, sin_historial: float, penalizar_cobertura: bool},
     *     cotizaciones: array<int, array{cotizacion_id: int, proveedor: string, precio: float, dias: int, cobertura: float, vencida: bool, confiabilidad: float, precio_norm: float, entrega_norm: float, confiabilidad_norm: float, puntaje: float}>,
     *     recomendada_id: int|null
     * }>
     */
    public function paraSolicitudes(array $solicitudIds): array
    {
        $parametros = $this->parametros();
        $ids = array_values(array_unique(array_map('intval', $solicitudIds)));

        $cotizacionesPorSolicitud = [];
        $proveedorIds = [];

        foreach ($ids as $solicitudId) {
            $cotizaciones = $this->query->cotizacionesDeSolicitud($solicitudId);
            $cotizacionesPorSolicitud[$solicitudId] = $cotizaciones;

            foreach ($cotizaciones as $cotizacion) {
                $proveedorIds[] = (int) $cotizacion->proveedor_id;
            }
        }

        $puntajes = $this->ranking->puntajesPorProveedor($proveedorIds);

        $informes = [];

        foreach ($cotizacionesPorSolicitud as $solicitudId => $cotizaciones) {
            $informes[$solicitudId] = $this->calcular($cotizaciones, $parametros, $puntajes);
        }

        return $informes;
    }

    /**
     * @param  Collection<int, Cotizacion>  $cotizaciones
     * @param  array{pesos: array<string, float>, sin_historial: float, penalizar_cobertura: bool}  $parametros
     * @param  array<int, array{puntaje: float, entregas: int}>  $puntajes
     * @return array{
     *     parametros: array{pesos: array<string, float>, sin_historial: float, penalizar_cobertura: bool},
     *     cotizaciones: array<int, array{cotizacion_id: int, proveedor: string, precio: float, dias: int, cobertura: float, vencida: bool, confiabilidad: float, precio_norm: float, entrega_norm: float, confiabilidad_norm: float, puntaje: float}>,
     *     recomendada_id: int|null
     * }
     */
    private function calcular(Collection $cotizaciones, array $parametros, array $puntajes): array
    {
        if ($cotizaciones->isEmpty()) {
            return [
                'parametros' => $parametros,
                'cotizaciones' => [],
                'recomendada_id' => null,
            ];
        }

        $filas = $cotizaciones
            ->map(fn (Cotizacion $cotizacion): array => $this->datosDeCotizacion($cotizacion, $parametros, $puntajes))
            ->values();

        $preciosValidos = $filas->pluck('precio')->filter(fn (float $precio): bool => $precio > 0);
        $precioMinimo = $preciosValidos->isEmpty() ? 0.0 : (float) $preciosValidos->min();

        // A quote whose promised date already passed has zero days: it is late, not instant,
        // so it anchors the comparison at zero days and scores 0 in this criterion.
        $diasValidos = $filas->pluck('dias')->filter(fn (int $dias): bool => $dias > 0);
        $diasMinimo = $diasValidos->isEmpty() ? 0 : (int) $diasValidos->min();

        $normalizadas = $filas->map(function (array $fila) use ($precioMinimo, $diasMinimo, $parametros): array {
            $precioNorm = $precioMinimo > 0 && $fila['precio'] > 0
                ? ($precioMinimo / $fila['precio']) * 100
                : ($fila['precio'] === $precioMinimo ? 100.0 : 0.0);

            $entregaNorm = $fila['dias'] > 0 && $diasMinimo > 0
                ? ($diasMinimo / $fila['dias']) * 100
                : ($fila['dias'] > 0 ? 100.0 : 0.0);

            $confiabilidadNorm = $fila['confiabilidad'];

            $puntaje = (
                $parametros['pesos']['precio'] * $precioNorm
                + $parametros['pesos']['entrega'] * $entregaNorm
                + $parametros['pesos']['confiabilidad'] * $confiabilidadNorm
            ) * ($parametros['penalizar_cobertura'] ? $fila['cobertura'] : 1.0);

            return [
                'cotizacion_id' => $fila['cotizacion_id'],
                'proveedor' => $fila['proveedor'],
                'precio' => round($fila['precio'], 2),
                'dias' => $fila['dias'],
                'cobertura' => round($fila['cobertura'], 4),
                'vencida' => $fila['vencida'],
                'confiabilidad' => round($fila['confiabilidad'], 2),
                'precio_norm' => round($precioNorm, 2),
                'entrega_norm' => round($entregaNorm, 2),
                'confiabilidad_norm' => round($confiabilidadNorm, 2),
                'puntaje' => round($puntaje, 2),
            ];
        })->values();

        return [
            'parametros' => $parametros,
            'cotizaciones' => $normalizadas->all(),
            'recomendada_id' => $this->recomendada($normalizadas),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $filas
     */
    private function recomendada(Collection $filas): ?int
    {
        if ($filas->count() <= 1) {
            return null;
        }

        $maximo = (float) $filas->max('puntaje');
        $ganadoras = $filas->filter(
            fn (array $fila): bool => abs((float) $fila['puntaje'] - $maximo) < 0.0001,
        );

        return $ganadoras->count() === 1 ? (int) $ganadoras->first()['cotizacion_id'] : null;
    }

    /**
     * @param  array{pesos: array<string, float>, sin_historial: float, penalizar_cobertura: bool}  $parametros
     * @param  array<int, array{puntaje: float, entregas: int}>  $puntajes
     * @return array{cotizacion_id: int, proveedor: string, precio: float, dias: int, cobertura: float, vencida: bool, confiabilidad: float}
     */
    private function datosDeCotizacion(Cotizacion $cotizacion, array $parametros, array $puntajes): array
    {
        $lineasPorArticulo = $cotizacion->lineas->keyBy('articulo_id');

        $precio = 0.0;
        $cobertura = 1.0;

        foreach ($cotizacion->solicitudDeCotizacion?->lineas ?? collect() as $lineaSolicitud) {
            /** @var LineaSolicitudCotizacion $lineaSolicitud */
            $cantidadSolicitada = (float) $lineaSolicitud->cantidad_solicitada;

            /** @var LineaCotizacion|null $lineaCotizacion */
            $lineaCotizacion = $lineasPorArticulo->get($lineaSolicitud->articulo_id);

            if ($lineaCotizacion === null) {
                $cobertura = 0.0;

                continue;
            }

            $precio += $cantidadSolicitada * (float) $lineaCotizacion->precio_unitario;

            if ($cantidadSolicitada > 0) {
                $cobertura = min(
                    $cobertura,
                    (float) $lineaCotizacion->cantidad_ofertada / $cantidadSolicitada,
                );
            }
        }

        $fechaPrometida = $cotizacion->lineas->pluck('fecha_prometida')->filter()->max();

        if ($fechaPrometida === null) {
            $dias = 0;
            $vencida = false;
        } else {
            $fecha = $fechaPrometida instanceof Carbon
                ? $fechaPrometida->copy()
                : Carbon::parse($fechaPrometida);

            $fecha->startOfDay();
            $hoy = today();
            $vencida = $fecha->lt($hoy);
            $dias = $vencida ? 0 : (int) $hoy->diffInDays($fecha, false);
        }

        return [
            'cotizacion_id' => (int) $cotizacion->id,
            'proveedor' => (string) $cotizacion->proveedor?->razon_social,
            'precio' => $precio,
            'dias' => $dias,
            'cobertura' => max(0.0, min(1.0, $cobertura)),
            'vencida' => $vencida,
            'confiabilidad' => (float) ($puntajes[(int) $cotizacion->proveedor_id]['puntaje'] ?? $parametros['sin_historial']),
        ];
    }

    /**
     * @return array{pesos: array<string, float>, sin_historial: float, penalizar_cobertura: bool}
     */
    private function parametros(): array
    {
        $precio = $this->numero(self::PESO_PRECIO, 0.4, 0.0, 1.0);
        $entrega = $this->numero(self::PESO_ENTREGA, 0.3, 0.0, 1.0);
        $confiabilidad = $this->numero(self::PESO_CONFIABILIDAD, 0.3, 0.0, 1.0);

        $suma = $precio + $entrega + $confiabilidad;

        if ($suma <= 0.0) {
            $precio = 0.4;
            $entrega = 0.3;
            $confiabilidad = 0.3;
            $suma = 1.0;
        }

        return [
            'pesos' => [
                'precio' => round($precio / $suma, 4),
                'entrega' => round($entrega / $suma, 4),
                'confiabilidad' => round($confiabilidad / $suma, 4),
            ],
            'sin_historial' => $this->numero(self::CONFIABILIDAD_SIN_HISTORIAL, 50.0, 0.0, 100.0),
            'penalizar_cobertura' => $this->numero(self::PENALIZAR_COBERTURA, 1.0, 0.0, 1.0) >= 1.0,
        ];
    }

    private function numero(string $clave, float $porDefecto, float $minimo, float $maximo): float
    {
        $valor = $this->parametros->parametro($clave);

        if ($valor === null || ! is_numeric($valor)) {
            return $porDefecto;
        }

        return max($minimo, min($maximo, (float) $valor));
    }
}
