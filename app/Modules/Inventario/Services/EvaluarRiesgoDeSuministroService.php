<?php

namespace App\Modules\Inventario\Services;

use App\Modules\Compras\Services\GestionSolicitudesDeCotizacionService;
use App\Modules\Predictivo\Services\EvaluarRiesgoSuministroService;
use App\Modules\Usuarios\Services\ParametroGlobalService;
use App\Modules\Usuarios\Services\UsuariosService;
use App\Shared\Alertas\Services\AlertaService;

/**
 * CU-PRED-03 origin process.
 *
 * Inventario owns the stock, so it is the module that reacts: it asks the
 * predictive module for the criticality list, emits or closes the stock-break
 * alerts through the shared kernel, asks Compras for a draft per critical
 * input, and — when the manager enabled it — fires the licitation once the
 * order-by date has arrived.
 */
class EvaluarRiesgoDeSuministroService
{
    public function __construct(
        private readonly EvaluarRiesgoSuministroService $predictivo,
        private readonly GestionSolicitudesDeCotizacionService $compras,
        private readonly AlertaService $alertas,
        private readonly ParametroGlobalService $parametros,
        private readonly UsuariosService $usuarios,
    ) {}

    /**
     * @return array{evaluados: int, criticos: int, alertas: int, borradores: int, licitaciones: int}
     */
    public function evaluar(?string $fechaBase = null): array
    {
        $riesgo = $this->predictivo->evaluar($fechaBase);

        $contextos = [];

        foreach ($riesgo['criticidad'] as $fila) {
            $contextos['articulo:'.$fila['articulo_id']] = [
                'articulo' => $fila['descripcion'] ?? 'Artículo '.$fila['articulo_id'],
                'disponible' => $fila['disponible'],
                'minimo' => $fila['stock_minimo'] ?? 0.0,
                'dias_hasta_quiebre' => $fila['dias_hasta_quiebre'],
                'fecha_limite_pedido' => $fila['fecha_limite_pedido'],
                'cantidad_sugerida' => $fila['cantidad_sugerida'],
                'articulo_id' => $fila['articulo_id'],
            ];
        }

        // Emits or refreshes one alert per critical input and closes the ones
        // that recovered; the risk list is exactly the set that must be open.
        $alertas = $this->alertas->sincronizarPorTipo('quiebre_de_stock', $contextos);

        $borradores = 0;

        foreach ($riesgo['criticidad'] as $fila) {
            $borrador = $this->compras->crearBorradorPorRiesgo(
                (int) $fila['articulo_id'],
                (float) $fila['cantidad_sugerida'],
            );

            if ($borrador !== null) {
                $borradores++;
            }
        }

        return [
            'evaluados' => (int) $riesgo['evaluados'],
            'criticos' => count($riesgo['criticidad']),
            'alertas' => $alertas,
            'borradores' => $borradores,
            'licitaciones' => $this->dispararLicitaciones($riesgo['criticidad']),
        ];
    }

    /**
     * Fire the pending drafts whose order-by date already arrived.
     *
     * @param  array<int, array<string, mixed>>  $criticidad
     */
    private function dispararLicitaciones(array $criticidad): int
    {
        if ($this->parametros->parametro('predictivo_licitacion_automatica') !== '1') {
            return 0;
        }

        $actorId = $this->usuarios->usuarioSistema()->id;
        $disparadas = 0;

        foreach ($criticidad as $fila) {
            if (($fila['riesgo'] ?? null) !== 'alto') {
                continue;
            }

            $solicitudId = $this->compras->idDeBorradorAbiertoParaArticulo((int) $fila['articulo_id']);

            if ($solicitudId === null) {
                continue;
            }

            $resultado = $this->compras->dispararLicitacion($solicitudId, $actorId);

            if ($resultado['disparada']) {
                $disparadas++;

                continue;
            }

            $this->alertas->emitirPorTipo('licitacion_no_disparada', [
                'articulo' => $fila['descripcion'] ?? 'Artículo '.$fila['articulo_id'],
                'motivo' => $this->motivoLicitacion($resultado['motivo']),
                'articulo_id' => $fila['articulo_id'],
            ], 'articulo:'.$fila['articulo_id'].':licitacion');
        }

        return $disparadas;
    }

    private function motivoLicitacion(?string $motivo): string
    {
        return match ($motivo) {
            'sin_proveedores_con_email' => 'No hay proveedores con email para invitar.',
            'estado_invalido' => 'La solicitud ya no está en borrador.',
            'solicitud_inexistente' => 'La solicitud no existe.',
            default => 'No se pudo disparar la licitación.',
        };
    }
}
