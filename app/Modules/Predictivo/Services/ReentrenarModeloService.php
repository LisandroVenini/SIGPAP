<?php

namespace App\Modules\Predictivo\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Produccion\Services\ConsultaProduccionService;
use App\Modules\Usuarios\Services\ParametroGlobalService;
use App\Modules\Usuarios\Services\UsuariosService;
use App\Shared\Alertas\Services\AlertaService;

/**
 * CU-PRED-01: retrain the model with the consolidated history.
 *
 * The component owns the model; this service compiles the batch, trains each
 * stage, records the audit event required by the contract and alerts the
 * administrator when the metrics drift beyond the configured threshold.
 */
class ReentrenarModeloService
{
    private const ACCION_AUDITORIA = 'Reentrenamiento del Modelo';

    private const MINIMO_MUESTRAS = 3;

    /** Engine failures that a later run can fix. */
    private const CODIGOS_TRANSITORIOS = ['proceso_fallido', 'timeout', 'respuesta_invalida'];

    public function __construct(
        private readonly MLEngine $engine,
        private readonly ConsultaProduccionService $produccion,
        private readonly AuditoriaService $auditoria,
        private readonly AlertaService $alertas,
        private readonly UsuariosService $usuarios,
        private readonly ParametroGlobalService $parametros,
    ) {}

    /**
     * @return array{entrenadas: int, omitidas: int, drift: array<int, array{etapa_id: int, delta: array{r2: float|null, mae: float|null}}>}
     */
    public function reentrenar(?string $desde = null, ?string $hasta = null): array
    {
        $hasta = $hasta ?? now()->toDateString();
        $desde = $desde ?? now()->subMonths($this->ventanaMeses())->toDateString();

        $actorId = $this->usuarios->usuarioSistema()->id;
        $umbral = $this->umbral();

        $entrenadas = 0;
        $omitidas = 0;
        $drift = [];

        foreach ($this->produccion->historicoDePartes($desde, $hasta) as $etapa) {
            $muestras = $etapa['muestras'];

            if (count($muestras) < self::MINIMO_MUESTRAS) {
                $omitidas++;

                continue;
            }

            $primera = $muestras[0];

            try {
                $resultado = $this->engine->entrenar([
                    'etapa_id' => $etapa['etapa_id'],
                    'tiempo_por_unidad' => $primera['tiempo_por_unidad'],
                    'minutos_habiles_por_dia' => $primera['minutos_habiles'] > 0 ? $primera['minutos_habiles'] : 480,
                    'muestras' => $muestras,
                ]);
            } catch (MLEngineException $error) {
                if ($this->esErrorTransitorio($error)) {
                    throw $error;
                }

                $omitidas++;

                continue;
            }

            $entrenadas++;

            $this->auditoria->registrarEvento(
                $actorId,
                self::ACCION_AUDITORIA,
                null,
                [
                    'etapa_id' => $etapa['etapa_id'],
                    'n_muestras' => $resultado['n_muestras'],
                    'metricas' => $resultado['metricas'],
                ],
                null,
                null,
                null,
            );

            if ($this->empeoro($resultado['delta'], $umbral)) {
                $drift[] = ['etapa_id' => $etapa['etapa_id'], 'delta' => $resultado['delta']];

                $this->alertas->emitirPorTipo('drift_detectado', [
                    'etapa' => $etapa['etapa'],
                    'r2_anterior' => $resultado['metricas_anteriores']['r2'] ?? '—',
                    'r2_actual' => $resultado['metricas']['r2'] ?? '—',
                    'umbral' => $umbral,
                ], 'etapa:'.$etapa['etapa_id']);
            }
        }

        return ['entrenadas' => $entrenadas, 'omitidas' => $omitidas, 'drift' => $drift];
    }

    /**
     * Whether the engine failure is transient and worth retrying later.
     */
    public function esErrorTransitorio(MLEngineException $error): bool
    {
        return in_array($error->codigo, self::CODIGOS_TRANSITORIOS, true);
    }

    /**
     * @param  array{r2: float|null, mae: float|null}  $delta
     */
    private function empeoro(array $delta, float $umbral): bool
    {
        $r2 = $delta['r2'] ?? null;
        $mae = $delta['mae'] ?? null;

        return ($r2 !== null && $r2 < -$umbral) || ($mae !== null && $mae > $umbral);
    }

    private function umbral(): float
    {
        $valor = $this->parametros->parametro('predictivo_drift_umbral');
        $umbral = $valor !== null ? (float) $valor : 0.1;

        return $umbral > 0 ? $umbral : 0.1;
    }

    private function ventanaMeses(): int
    {
        $valor = $this->parametros->parametro('predictivo_ventana_entrenamiento_meses');
        $meses = $valor !== null ? (int) $valor : 12;

        return $meses > 0 ? $meses : 12;
    }
}
