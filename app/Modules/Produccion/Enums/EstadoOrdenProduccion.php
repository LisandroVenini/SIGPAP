<?php

namespace App\Modules\Produccion\Enums;

enum EstadoOrdenProduccion: string
{
    case Pendiente = 'Pendiente';
    case PendientePorInsumos = 'Pendiente por Insumos';
    case Asignada = 'Asignada';
    case EnCurso = 'En Curso';
    case Pospuesta = 'Pospuesta';
    case Cerrada = 'Cerrada';
    case Anulada = 'Anulada';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
