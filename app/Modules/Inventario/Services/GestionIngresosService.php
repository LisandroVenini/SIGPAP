<?php

namespace App\Modules\Inventario\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Compras\Services\GestionRecepcionesService;
use App\Modules\Inventario\Enums\TipoMovimientoStock;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Models\MovimientoDeStock;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionIngresosService
{
    public function __construct(
        private readonly AuditoriaService $auditoria,
        private readonly GestionRecepcionesService $recepciones,
    ) {}

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
    ): array {
        $orden = $this->recepciones->buscarPorNumero($numeroOc);

        if ($orden === null) {
            throw ValidationException::withMessages([
                'numero_oc' => 'La orden de compra no existe o no está pendiente de recepción.',
            ]);
        }

        $this->validarLineas($orden['lineas'], $lineas, $confirmarExcedente);

        DB::transaction(function () use ($actor, $numeroOc, $numeroRemito, $lineas, $orden, $entregaParcial, $direccionIp): void {
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
            );
        });

        return [
            'orden' => $numeroOc,
            'recibidas' => count($lineas),
        ];
    }

    /**
     * @param  array<int, array{articulo_id: int, cantidad: float, precio_pactado: float}>  $lineasOrden
     * @param  array<int, array{articulo_id: int, cantidad: float|int|string}>  $lineas
     */
    private function validarLineas(array $lineasOrden, array $lineas, bool $confirmarExcedente): void
    {
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

            if (! $confirmarExcedente && $cantidad > (float) $pedidas->get($articuloId)['cantidad']) {
                throw ValidationException::withMessages([
                    'lineas' => 'Se recibió más cantidad de la pedida. Confirmá el excedente para continuar.',
                ]);
            }

            $vistas[$articuloId] = true;
        }
    }
}
