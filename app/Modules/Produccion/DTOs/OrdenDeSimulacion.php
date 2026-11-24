<?php

namespace App\Modules\Produccion\DTOs;

use Carbon\CarbonImmutable;

/**
 * A hypothetical order used to project a scenario without persisting anything.
 *
 * It mirrors the attributes the projection reads, so the aggregator can treat
 * real orders and simulated ones the same way. It lives in Producción because
 * scheduling owns the projection; other modules pass this DTO through the
 * public service instead of building an order model.
 */
final class OrdenDeSimulacion
{
    public function __construct(
        public readonly int $id,
        public readonly int $articulo_id,
        public readonly float $cantidad_requerida,
        public readonly string $estado,
        public readonly ?CarbonImmutable $fecha_entrega,
        public readonly int $prioridad_actual,
        public readonly ?CarbonImmutable $fecha_prevista_inicio = null,
    ) {}
}
