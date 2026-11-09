<?php

namespace App\Modules\Compras\Services;

use App\Modules\Compras\Models\DesempenoProveedor;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Queries\RankingProveedoresQuery;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\ParametroGlobalService;
use App\Modules\Usuarios\Services\UsuariosService;
use Illuminate\Support\Collection;

class GestionRankingService
{
    private const PESO_PERFECTA = 'compras_ranking_peso_perfecta';

    private const PESO_RETRASO = 'compras_ranking_peso_retraso';

    private const PESO_FALTANTE = 'compras_ranking_peso_faltante';

    private const PESO_ROTURA = 'compras_ranking_peso_rotura';

    private const VENTANA_MESES = 'compras_ranking_ventana_meses';

    private const VENTANA_POR_DEFECTO = 12;

    /**
     * @var array<string, float>
     */
    private const PESOS_POR_DEFECTO = [
        'perfecta' => 1.0,
        'retraso' => 0.6,
        'faltante' => 0.3,
        'rotura' => 0.0,
    ];

    public function __construct(
        private readonly ParametroGlobalService $parametros,
        private readonly RankingProveedoresQuery $ranking,
        private readonly UsuariosService $usuarios,
    ) {}

    public function puedeVer(Usuario $actor): bool
    {
        return $this->usuarios->autorizar($actor, 'compras.gestionar');
    }

    /**
     * @return array{
     *     parametros: array{pesos: array<string, float>, ventana_meses: int},
     *     filas: array<int, array{proveedor_id: int, proveedor: string, entregas: int, perfectas: int, retrasos: int, faltantes: int, roturas: int, puntaje: float}>,
     *     sin_historial: array<int, array{proveedor_id: int, proveedor: string}>
     * }
     */
    public function listar(): array
    {
        $pesos = $this->pesos();
        $ventana = $this->ventanaMeses();

        $entregas = $this->ranking
            ->entregasEnVentana($ventana)
            ->groupBy(fn (DesempenoProveedor $entrega): int => (int) $entrega->proveedor_id);

        $filas = [];
        $sinHistorial = [];

        foreach (Proveedor::query()->activos()->get(['id', 'razon_social']) as $proveedor) {
            $desempenos = $entregas->get((int) $proveedor->id);

            if ($desempenos === null || $desempenos->isEmpty()) {
                $sinHistorial[] = [
                    'proveedor_id' => (int) $proveedor->id,
                    'proveedor' => $proveedor->razon_social,
                ];

                continue;
            }

            $filas[] = [
                'proveedor_id' => (int) $proveedor->id,
                'proveedor' => $proveedor->razon_social,
                ...$this->resumen($desempenos, $pesos),
            ];
        }

        usort(
            $filas,
            fn (array $a, array $b): int => ($b['puntaje'] <=> $a['puntaje'])
                ?: strcmp($a['proveedor'], $b['proveedor']),
        );

        return [
            'parametros' => [
                'pesos' => $pesos,
                'ventana_meses' => $ventana,
            ],
            'filas' => $filas,
            'sin_historial' => $sinHistorial,
        ];
    }

    /**
     * Scores for the requested suppliers, reusing the ranking rules.
     *
     * @param  array<int, int>  $proveedorIds
     * @return array<int, array{puntaje: float, entregas: int}>
     */
    public function puntajesPorProveedor(array $proveedorIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $proveedorIds)));

        if ($ids === []) {
            return [];
        }

        $pesos = $this->pesos();

        $entregas = $this->ranking
            ->entregasEnVentana($this->ventanaMeses())
            ->whereIn('proveedor_id', $ids)
            ->groupBy(fn (DesempenoProveedor $entrega): int => (int) $entrega->proveedor_id);

        $resultado = [];

        foreach ($ids as $proveedorId) {
            $desempenos = $entregas->get($proveedorId);

            if ($desempenos === null || $desempenos->isEmpty()) {
                continue;
            }

            $resumen = $this->resumen($desempenos, $pesos);

            $resultado[$proveedorId] = [
                'puntaje' => $resumen['puntaje'],
                'entregas' => $resumen['entregas'],
            ];
        }

        return $resultado;
    }

    /**
     * @param  Collection<int, DesempenoProveedor>  $desempenos
     * @param  array<string, float>  $pesos
     * @return array{entregas: int, perfectas: int, retrasos: int, faltantes: int, roturas: int, puntaje: float}
     */
    private function resumen(Collection $desempenos, array $pesos): array
    {
        $conteos = ['perfecta' => 0, 'retraso' => 0, 'faltante' => 0, 'rotura' => 0];
        $pesoAcumulado = 0.0;

        foreach ($desempenos as $desempeno) {
            $categoria = $this->categoriaDeEntrega(
                $desempeno->ordenDeCompra?->incidencias->pluck('tipo')->all() ?? [],
            );

            $conteos[$categoria]++;
            $pesoAcumulado += $pesos[$categoria];
        }

        $cantidad = $desempenos->count();

        return [
            'entregas' => $cantidad,
            'perfectas' => $conteos['perfecta'],
            'retrasos' => $conteos['retraso'],
            'faltantes' => $conteos['faltante'],
            'roturas' => $conteos['rotura'],
            'puntaje' => round(($pesoAcumulado / $cantidad) * 100, 2),
        ];
    }

    /**
     * The worst incident of the order determines the weight of the delivery.
     *
     * @param  array<int, string>  $tipos
     */
    private function categoriaDeEntrega(array $tipos): string
    {
        $tipos = array_map(
            fn (string $tipo): string => strtolower(trim($tipo)),
            $tipos,
        );

        foreach (['rotura', 'faltante', 'retraso'] as $categoria) {
            if (in_array($categoria, $tipos, true)) {
                return $categoria;
            }
        }

        return 'perfecta';
    }

    /**
     * @return array<string, float>
     */
    private function pesos(): array
    {
        return [
            'perfecta' => $this->peso(self::PESO_PERFECTA, self::PESOS_POR_DEFECTO['perfecta']),
            'retraso' => $this->peso(self::PESO_RETRASO, self::PESOS_POR_DEFECTO['retraso']),
            'faltante' => $this->peso(self::PESO_FALTANTE, self::PESOS_POR_DEFECTO['faltante']),
            'rotura' => $this->peso(self::PESO_ROTURA, self::PESOS_POR_DEFECTO['rotura']),
        ];
    }

    private function peso(string $clave, float $porDefecto): float
    {
        $valor = $this->parametros->parametro($clave);

        if ($valor === null || ! is_numeric($valor)) {
            return $porDefecto;
        }

        return max(0.0, min(1.0, (float) $valor));
    }

    private function ventanaMeses(): int
    {
        $valor = $this->parametros->parametro(self::VENTANA_MESES);

        if ($valor === null || filter_var($valor, FILTER_VALIDATE_INT) === false) {
            return self::VENTANA_POR_DEFECTO;
        }

        return max(1, min(60, (int) $valor));
    }
}
