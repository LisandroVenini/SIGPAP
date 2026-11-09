<?php

namespace App\Modules\Reportes\Services;

use App\Modules\Compras\Services\GestionOrdenesDeCompraService;
use App\Modules\Inventario\Services\GestionArticulosService;
use App\Modules\Produccion\Services\GestionMantenimientosService;
use App\Modules\Produccion\Services\GestionOrdenesDeProduccionService;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\UsuariosService;

class DashboardService
{
    /**
     * Number of blocked machines listed on the dashboard.
     */
    private const LIMITE_MAQUINAS_BLOQUEADAS = 5;

    public function __construct(
        private readonly UsuariosService $usuarios,
        private readonly GestionOrdenesDeProduccionService $produccion,
        private readonly GestionArticulosService $inventario,
        private readonly GestionOrdenesDeCompraService $compras,
        private readonly GestionMantenimientosService $mantenimientos,
    ) {}

    /**
     * Build the dashboard summary with only the areas the actor may access.
     *
     * @return array{produccion: array<string, mixed>|null, inventario: array<string, mixed>|null, compras: array<string, mixed>|null, mantenimiento: array<string, mixed>|null}
     */
    public function resumen(Usuario $actor): array
    {
        $puedeProduccion = $this->usuarios->autorizar($actor, 'produccion.gestionar');

        $produccion = $puedeProduccion
            ? $this->produccion->resumenDashboard()
            : null;

        $mantenimiento = $puedeProduccion
            ? [
                ...$this->mantenimientos->resumenDashboard(),
                'bloqueadas' => $this->mantenimientos->maquinasBloqueadas(self::LIMITE_MAQUINAS_BLOQUEADAS),
            ]
            : null;

        $inventario = $this->usuarios->autorizar($actor, 'inventario.gestionar')
            ? $this->inventario->resumenDashboard()
            : null;

        $compras = $this->usuarios->autorizar($actor, 'compras.gestionar')
            ? $this->compras->resumenDashboard()
            : null;

        return [
            'produccion' => $produccion,
            'inventario' => $inventario,
            'compras' => $compras,
            'mantenimiento' => $mantenimiento,
        ];
    }
}
