<?php

namespace App\Modules\Reportes\Services;

use App\Modules\Compras\Services\GestionOrdenesDeCompraService;
use App\Modules\Inventario\Services\ConsultaMovimientosService;
use App\Modules\Produccion\Services\GestionOrdenesDeProduccionService;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\UsuariosService;
use Illuminate\Validation\ValidationException;

class GestionReportesService
{
    /**
     * Report types with their display title and the permission of their area.
     *
     * @var array<string, array{titulo: string, permiso: string}>
     */
    public const TIPOS = [
        'produccion' => ['titulo' => 'Producción', 'permiso' => 'produccion.gestionar'],
        'inventario' => ['titulo' => 'Inventario', 'permiso' => 'inventario.gestionar'],
        'compras' => ['titulo' => 'Compras', 'permiso' => 'compras.gestionar'],
    ];

    /**
     * Human readable headers in the same order as each query row.
     *
     * @var array<string, array<string, string>>
     */
    private const COLUMNAS = [
        'produccion' => [
            'orden_id' => 'Orden',
            'producto' => 'Producto',
            'planificacion' => 'Planificación',
            'estado' => 'Estado',
            'cantidad_requerida' => 'Cantidad requerida',
            'cantidad_producida' => 'Cantidad producida',
            'fecha_entrega' => 'Fecha de entrega',
            'fecha_inicio' => 'Fecha de inicio',
            'fecha_fin' => 'Fecha de fin',
        ],
        'inventario' => [
            'fecha' => 'Fecha',
            'articulo' => 'Artículo',
            'tipo' => 'Tipo',
            'cantidad' => 'Cantidad',
            'numero_remito' => 'Número de remito',
            'motivo' => 'Motivo',
        ],
        'compras' => [
            'numero_oc' => 'Número de OC',
            'proveedor' => 'Proveedor',
            'estado' => 'Estado',
            'fecha_emision' => 'Fecha de emisión',
            'lineas' => 'Líneas',
            'total' => 'Total',
        ],
    ];

    public function __construct(
        private readonly UsuariosService $usuarios,
        private readonly GestionOrdenesDeProduccionService $produccion,
        private readonly ConsultaMovimientosService $inventario,
        private readonly GestionOrdenesDeCompraService $compras,
    ) {}

    public static function permisoDe(string $tipo): ?string
    {
        return self::TIPOS[$tipo]['permiso'] ?? null;
    }

    /**
     * @return array<string, string> type key => title
     */
    public function tiposDisponibles(Usuario $actor): array
    {
        $disponibles = [];

        foreach (self::TIPOS as $tipo => $configuracion) {
            if ($this->usuarios->autorizar($actor, $configuracion['permiso'])) {
                $disponibles[$tipo] = $configuracion['titulo'];
            }
        }

        return $disponibles;
    }

    public function puedeExportar(Usuario $actor, string $tipo): bool
    {
        return array_key_exists($tipo, $this->tiposDisponibles($actor));
    }

    /**
     * @param  array{desde?: string|null, hasta?: string|null, estado?: string|null}  $filtros
     * @return array{tipo: string, titulo: string, columnas: array<int, string>, filas: array<int, array<string, mixed>>, total: int, desde: string, hasta: string}
     */
    public function generar(Usuario $actor, string $tipo, array $filtros): array
    {
        if (! $this->puedeExportar($actor, $tipo)) {
            throw ValidationException::withMessages([
                'tipo' => 'El tipo de reporte no está disponible para el usuario.',
            ]);
        }

        $desde = (string) ($filtros['desde'] ?? '');
        $hasta = (string) ($filtros['hasta'] ?? '');

        if ($desde === '' || $hasta === '') {
            throw ValidationException::withMessages([
                'desde' => 'El rango de fechas es obligatorio.',
            ]);
        }

        $filas = match ($tipo) {
            'produccion' => $this->produccion->reportePorFechaDeEntrega($desde, $hasta, $filtros['estado'] ?? null),
            'inventario' => $this->inventario->reportePorFecha($desde, $hasta),
            'compras' => $this->compras->reportePorFechaDeEmision($desde, $hasta),
        };

        return [
            'tipo' => $tipo,
            'titulo' => self::TIPOS[$tipo]['titulo'],
            'columnas' => array_values(self::COLUMNAS[$tipo]),
            'filas' => $filas,
            'total' => count($filas),
            'desde' => $desde,
            'hasta' => $hasta,
        ];
    }
}
