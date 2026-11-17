<?php

namespace App\Modules\Inventario\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Compras\Enums\EstadoOrdenDeCompra;
use App\Modules\Compras\Services\GestionRecepcionesService;
use App\Modules\Inventario\Enums\TipoMovimientoStock;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Models\MovimientoDeStock;
use App\Modules\Inventario\Queries\IngresosRegistradosQuery;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionIngresosService
{
    public function __construct(
        private readonly AuditoriaService $auditoria,
        private readonly GestionRecepcionesService $recepciones,
        private readonly IngresosRegistradosQuery $ingresosRegistrados,
    ) {}

    /**
     * Registered receipts, with the purchase order number resolved by its owner module.
     *
     * @return Collection<int, array{orden_id:int, numero_oc:string, numero_remito:string, fecha:string|null, lineas:int, unidades:float}>
     */
    public function registrados(): Collection
    {
        $grupos = $this->ingresosRegistrados->listar();
        $numeros = $this->recepciones->numerosDeOrden($grupos->pluck('orden_id')->unique()->values()->all());

        return $grupos->map(fn (array $grupo): array => [
            ...$grupo,
            'numero_oc' => $numeros[$grupo['orden_id']] ?? '—',
        ]);
    }

    /**
     * Undo a registered receipt: reverse the stock and let the purchase order be
     * received again.
     */
    public function anular(
        Usuario $actor,
        string $numeroOc,
        string $numeroRemito,
        ?string $direccionIp = null,
    ): void {
        DB::transaction(function () use ($actor, $numeroOc, $numeroRemito, $direccionIp): void {
            $orden = $this->recepciones->ordenPorNumero($numeroOc);

            $tieneRecepcion = $orden !== null && (
                $orden['estado'] === EstadoOrdenDeCompra::Recibida->value
                || ($orden['estado'] === EstadoOrdenDeCompra::Confirmada->value && $orden['entrega_parcial'])
            );

            if (! $tieneRecepcion) {
                throw ValidationException::withMessages([
                    'numero_oc' => 'La orden de compra no tiene una recepción registrada.',
                ]);
            }

            $movimientos = MovimientoDeStock::query()
                ->where('orden_de_compra_id', $orden['id'])
                ->where('numero_remito', $numeroRemito)
                ->where('tipo', TipoMovimientoStock::Ingreso->value)
                ->lockForUpdate()
                ->get();

            if ($movimientos->isEmpty()) {
                throw ValidationException::withMessages([
                    'numero_remito' => 'No hay un ingreso registrado con ese remito para la orden indicada.',
                ]);
            }

            foreach ($movimientos as $movimiento) {
                $articulo = Articulo::query()
                    ->whereKey($movimiento->articulo_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $stockAnterior = (float) $articulo->stock_fisico;
                $stockNuevo = round($stockAnterior - (float) $movimiento->cantidad, 2);

                if ($stockNuevo < 0) {
                    throw ValidationException::withMessages([
                        'stock' => "El artículo {$articulo->descripcion} no tiene stock suficiente para anular el ingreso.",
                    ]);
                }

                $articulo->stock_fisico = $stockNuevo;
                $articulo->save();

                MovimientoDeStock::create([
                    'articulo_id' => $articulo->id,
                    'orden_de_compra_id' => $movimiento->orden_de_compra_id,
                    'tipo' => TipoMovimientoStock::AnulacionIngreso->value,
                    'cantidad' => (float) $movimiento->cantidad,
                    'fecha' => today()->toDateString(),
                    'motivo' => 'Anulación de ingreso',
                    'numero_remito' => $numeroRemito,
                ]);

                $this->auditoria->registrarEvento(
                    $actor->id,
                    'anular_ingreso_mercancia',
                    ['stock_fisico' => $stockAnterior],
                    [
                        'stock_fisico' => $stockNuevo,
                        'numero_remito' => $numeroRemito,
                        'orden_de_compra' => $numeroOc,
                    ],
                    $direccionIp,
                    Articulo::class,
                    $articulo->id,
                );
            }

            $netos = $this->cantidadesIngresadas($orden['id']);
            $quedaRecepcionParcial = collect($netos)->contains(fn (float $total): bool => $total > 0.0001);

            $this->recepciones->revertirRecepcionPorNumero($actor, $numeroOc, $direccionIp, $quedaRecepcionParcial);
        });
    }

    /**
     * Register the physical reception of merchandise against a purchase order.
     *
     * @param  array<int, array{articulo_id: int, cantidad: float|int|string}>  $lineas
     * @return array{orden: string, recibidas: int}
     */
    public function registrar(
        Usuario $actor,
        string $numeroOc,
        string $numeroRemito,
        array $lineas,
        bool $entregaParcial,
        bool $confirmarExcedente,
        ?string $direccionIp = null,
        bool $cerrarConFaltantes = false,
    ): array {
        $orden = $this->recepciones->buscarPorNumero($numeroOc);

        if ($orden === null) {
            throw ValidationException::withMessages([
                'numero_oc' => 'La orden de compra no existe o no está pendiente de recepción.',
            ]);
        }

        DB::transaction(function () use ($actor, $numeroOc, $numeroRemito, $lineas, $orden, $entregaParcial, $direccionIp, $confirmarExcedente, $cerrarConFaltantes): void {
            $this->recepciones->bloquearOrdenPorNumero($numeroOc);

            $acumuladas = $this->cantidadesIngresadas($orden['id']);

            $this->validarLineas($orden['lineas'], $lineas, $confirmarExcedente, $acumuladas);

            foreach ($lineas as $linea) {
                $articulo = Articulo::query()
                    ->whereKey((int) $linea['articulo_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $cantidad = (float) $linea['cantidad'];
                $stockAnterior = (float) $articulo->stock_fisico;
                $stockNuevo = $stockAnterior + $cantidad;

                $articulo->stock_fisico = $stockNuevo;
                $articulo->save();

                MovimientoDeStock::create([
                    'articulo_id' => $articulo->id,
                    'orden_de_compra_id' => $orden['id'],
                    'tipo' => TipoMovimientoStock::Ingreso->value,
                    'cantidad' => $cantidad,
                    'fecha' => today()->toDateString(),
                    'motivo' => 'Recepción de mercancía',
                    'numero_remito' => $numeroRemito,
                ]);

                $this->auditoria->registrarEvento(
                    $actor->id,
                    'registrar_ingreso_mercancia',
                    ['stock_fisico' => $stockAnterior],
                    [
                        'stock_fisico' => $stockNuevo,
                        'numero_remito' => $numeroRemito,
                        'orden_de_compra_id' => $orden['id'],
                    ],
                    $direccionIp,
                    Articulo::class,
                    $articulo->id,
                );
            }

            $this->recepciones->registrarRecepcionPorNumero(
                $actor,
                $numeroOc,
                $entregaParcial,
                $lineas,
                $direccionIp,
                $acumuladas,
                $cerrarConFaltantes,
            );
        });

        return [
            'orden' => $numeroOc,
            'recibidas' => count($lineas),
        ];
    }

    /**
     * Net received quantity per article, discounting annulled receipts.
     *
     * @return array<int, float>
     */
    private function cantidadesIngresadas(int $ordenDeCompraId): array
    {
        return MovimientoDeStock::query()
            ->where('orden_de_compra_id', $ordenDeCompraId)
            ->whereIn('tipo', [
                TipoMovimientoStock::Ingreso->value,
                TipoMovimientoStock::AnulacionIngreso->value,
            ])
            ->groupBy('articulo_id')
            ->selectRaw(
                'articulo_id, sum(case when tipo = ? then cantidad else -cantidad end) as total',
                [TipoMovimientoStock::Ingreso->value],
            )
            ->pluck('total', 'articulo_id')
            ->mapWithKeys(fn ($total, $id): array => [(int) $id => (float) $total])
            ->all();
    }

    private function validarLineas(
        array $lineasOrden,
        array $lineas,
        bool $confirmarExcedente,
        array $acumuladas = [],
    ): void {
        $pedidas = collect($lineasOrden)->keyBy('articulo_id');
        $vistas = [];

        foreach (array_values($lineas) as $indice => $linea) {
            $articuloId = (int) ($linea['articulo_id'] ?? 0);
            $cantidad = (float) ($linea['cantidad'] ?? 0);

            if (! $pedidas->has($articuloId)) {
                throw ValidationException::withMessages([
                    'lineas' => 'Todas las líneas deben pertenecer a la orden de compra.',
                ]);
            }

            if ($cantidad <= 0) {
                throw ValidationException::withMessages([
                    "lineas.{$indice}.cantidad" => 'La cantidad debe ser mayor a 0.',
                ]);
            }

            if (isset($vistas[$articuloId])) {
                throw ValidationException::withMessages([
                    'lineas' => 'No se puede repetir un artículo en la recepción.',
                ]);
            }

            $total = round(($acumuladas[$articuloId] ?? 0.0) + $cantidad, 2);

            if (! $confirmarExcedente && $total > round((float) $pedidas->get($articuloId)['cantidad'], 2)) {
                throw ValidationException::withMessages([
                    'lineas' => 'El total recibido supera la cantidad pedida. Confirmá el excedente para continuar.',
                ]);
            }

            $vistas[$articuloId] = true;
        }
    }
}
