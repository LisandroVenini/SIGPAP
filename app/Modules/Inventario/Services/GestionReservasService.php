<?php

namespace App\Modules\Inventario\Services;

use App\Modules\Inventario\Enums\EstadoReserva;
use App\Modules\Inventario\Models\Articulo;
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
