<?php

namespace App\Modules\Inventario\Services;

use App\Modules\Inventario\Enums\EstadoReserva;
use App\Modules\Inventario\Enums\TipoMovimientoStock;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Models\MovimientoDeStock;
use App\Modules\Inventario\Models\Reserva;
use App\Modules\Inventario\Queries\ReservasQuery;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionReservasService
{
    public function __construct(private readonly ReservasQuery $query) {}

    /**
     * Available stock is the physical stock minus the current reservations.
     * It never goes below zero: an order can never commit more than what exists.
     *
     * @param  array<int, int>  $articuloIds
     * @return array<int, float>
     */
    public function disponibles(array $articuloIds): array
    {
        if ($articuloIds === []) {
            return [];
        }

        $stock = Articulo::query()
            ->whereKey($articuloIds)
            ->pluck('stock_fisico', 'id');

        return $this->calcularDisponibles($articuloIds, $stock, $this->query->comprometidoPorArticulo($articuloIds));
    }

    /**
     * Reserve every requested quantity or none at all.
     *
     * @param  array<int, float>  $cantidades  articuloId => required quantity
     * @return array{faltantes: array<int, float>, cubierto: bool}
     */
    public function reservar(int $ordenDeProduccionId, array $cantidades): array
    {
        $this->asegurarCantidadesPositivas($cantidades);

        return DB::transaction(function () use ($ordenDeProduccionId, $cantidades): array {
            $articuloIds = array_keys($cantidades);

            // Locking the article rows serializes concurrent reservations for the same insumos.
            $stock = Articulo::query()
                ->whereKey($articuloIds)
                ->lockForUpdate()
                ->pluck('stock_fisico', 'id');

            $disponibles = $this->calcularDisponibles(
                $articuloIds,
                $stock,
                $this->query->comprometidoPorArticulo($articuloIds),
            );

            $faltantes = [];

            foreach ($cantidades as $articuloId => $cantidad) {
                $falta = round($cantidad - ($disponibles[$articuloId] ?? 0), 2);

                if ($falta > 0) {
                    $faltantes[$articuloId] = $falta;
                }
            }

            if ($faltantes !== []) {
                return ['faltantes' => $faltantes, 'cubierto' => false];
            }

            foreach ($cantidades as $articuloId => $cantidad) {
                Reserva::query()->create([
                    'articulo_id' => $articuloId,
                    'orden_de_produccion_id' => $ordenDeProduccionId,
                    'cantidad' => $cantidad,
                    'estado' => EstadoReserva::Vigente->value,
                ]);
            }

            return ['faltantes' => [], 'cubierto' => true];
        });
    }

    public function liberar(int $ordenDeProduccionId): void
    {
        Reserva::query()
            ->where('orden_de_produccion_id', $ordenDeProduccionId)
            ->where('estado', EstadoReserva::Vigente->value)
            ->update(['estado' => EstadoReserva::Liberada->value]);
    }

    /**
     * Replace the current reservations with the new quantities.
     *
     * @param  array<int, float>  $cantidades  articuloId => required quantity
     * @return array{faltantes: array<int, float>, cubierto: bool}
     */
    public function reemplazar(int $ordenDeProduccionId, array $cantidades): array
    {
        $this->asegurarCantidadesPositivas($cantidades);

        return DB::transaction(function () use ($ordenDeProduccionId, $cantidades): array {
            $this->liberar($ordenDeProduccionId);

            return $this->reservar($ordenDeProduccionId, $cantidades);
        });
    }

    /**
     * @param  array<int, int>  $articuloIds
     * @param  Collection<int, mixed>  $stock
     * @param  array<int, float>  $comprometido
     * @return array<int, float>
     */
    private function calcularDisponibles(array $articuloIds, Collection $stock, array $comprometido): array
    {
        $disponibles = [];

        foreach ($articuloIds as $articuloId) {
            $fisico = (float) ($stock[$articuloId] ?? 0);
            $reservado = $comprometido[$articuloId] ?? 0.0;
            $disponibles[$articuloId] = max(0.0, round($fisico - $reservado, 2));
        }

        return $disponibles;
    }

    /**
     * Check whether the current reservations for the order cover every need.
     *
     * @param  array<int, float>  $necesidades  articuloId => required quantity
     */
    public function cubierta(int $ordenDeProduccionId, array $necesidades): bool
    {
        if ($necesidades === []) {
            return true;
        }

        $comprometido = $this->query->comprometidoDeOrden($ordenDeProduccionId);

        foreach ($necesidades as $articuloId => $cantidad) {
            $reservado = $comprometido[$articuloId] ?? 0.0;

            if (round($cantidad - $reservado, 2) > 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, float>  $cantidades  articuloId => quantity to consume
     * @return array<int, float>
     */
    public function consumir(int $ordenDeProduccionId, array $cantidades): array
    {
        $this->asegurarCantidadesPositivas($cantidades);

        return DB::transaction(function () use ($ordenDeProduccionId, $cantidades): array {
            $articuloIds = array_keys($cantidades);

            // Lock article rows to serialize concurrent reservations and consumptions.
            $articulos = Articulo::query()
                ->whereKey($articuloIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $consumido = [];

            foreach ($cantidades as $articuloId => $cantidad) {
                $articulo = $articulos->get($articuloId);

                if ($articulo === null) {
                    throw ValidationException::withMessages([
                        'insumos' => 'No hay reserva suficiente para consumir los insumos de la orden.',
                    ]);
                }

                $reservas = Reserva::query()
                    ->where('orden_de_produccion_id', $ordenDeProduccionId)
                    ->where('articulo_id', $articuloId)
                    ->where('estado', EstadoReserva::Vigente->value)
                    ->orderBy('id')
                    ->get();

                $reservado = (float) $reservas->sum('cantidad');

                if (round($cantidad - $reservado, 2) > 0) {
                    throw ValidationException::withMessages([
                        'insumos' => 'No hay reserva suficiente para consumir los insumos de la orden.',
                    ]);
                }

                $pendiente = $cantidad;

                foreach ($reservas as $reserva) {
                    $disponibleDeReserva = (float) $reserva->cantidad;

                    if ($pendiente >= $disponibleDeReserva - 0.0001) {
                        // Consume the whole reservation.
                        $reserva->estado = EstadoReserva::Consumida->value;
                        $reserva->save();

                        $pendiente = round($pendiente - $disponibleDeReserva, 2);
                    } else {
                        // Partial: reduce the current reservation and create a consumed row for the history.
                        $reserva->cantidad = round($disponibleDeReserva - $pendiente, 2);
                        $reserva->save();

                        Reserva::query()->create([
                            'articulo_id' => $articuloId,
                            'orden_de_produccion_id' => $ordenDeProduccionId,
                            'cantidad' => $pendiente,
                            'estado' => EstadoReserva::Consumida->value,
                        ]);

                        $pendiente = 0;
                    }

                    if ($pendiente <= 0.0001) {
                        break;
                    }
                }

                $nuevoStock = round((float) $articulo->stock_fisico - $cantidad, 2);

                if ($nuevoStock < -0.0001) {
                    throw ValidationException::withMessages([
                        'stock' => 'El stock no puede quedar negativo.',
                    ]);
                }

                $articulo->stock_fisico = $nuevoStock;
                $articulo->save();

                MovimientoDeStock::create([
                    'articulo_id' => $articuloId,
                    'orden_de_produccion_id' => $ordenDeProduccionId,
                    'tipo' => TipoMovimientoStock::Consumo->value,
                    'cantidad' => -$cantidad,
                    'fecha' => now()->toDateString(),
                    'motivo' => 'Consumo por parte diario',
                ]);

                $consumido[$articuloId] = $cantidad;
            }

            return $consumido;
        });
    }

    /**
     * @param  array<int, float>  $cantidades
     */
    private function asegurarCantidadesPositivas(array $cantidades): void
    {
        foreach ($cantidades as $cantidad) {
            if ($cantidad <= 0) {
                throw ValidationException::withMessages([
                    'cantidad' => 'Las cantidades a reservar deben ser mayores a 0.',
                ]);
            }
        }
    }
}
