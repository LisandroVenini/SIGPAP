<?php

namespace App\Modules\Produccion\Enums;

enum MotivoCambioPrioridad: string
{
    case UrgenciaComercial = 'Urgencia comercial';
    case FaltaDeInsumos = 'Falta de insumos';
    case DecisionGerencial = 'Decisión gerencial';
    case Otro = 'Otro';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
