<?php

namespace App\Modules\Inventario\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Inventario\Enums\EstadoReserva;
use App\Modules\Inventario\Enums\TipoMovimientoStock;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Models\MovimientoDeStock;
use App\Modules\Inventario\Models\Reserva;
use App\Modules\Usuarios\Models\Usuario;
use App\Shared\Alertas\Services\AlertaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionAjustesService
{
    public function __construct(
        private readonly AuditoriaService $auditoria,
        private readonly AlertaService $alertas,
        private readonly EvaluarStockMinimoService $stockMinimo,
    ) {}

    public function ajustar(
        Usuario $actor,
        Articulo $articulo,
        float $nuevoStock,
        string $motivo,
        ?string $direccionIp = null,
    ): Articulo {
        if ($nuevoStock < 0) {
            throw ValidationException::withMessages([
                'nuevo_stock' => 'El stock no puede ser negativo.',
            ]);
        }

        $ajustado = DB::transaction(function () use ($actor, $articulo, $nuevoStock, $motivo, $direccionIp): Articulo {
            $articulo = Articulo::query()
                ->whereKey($articulo->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $stockAnterior = (float) $articulo->stock_fisico;
            $diferencia = $nuevoStock - $stockAnterior;

            if (abs($diferencia) < 0.0001) {
                throw ValidationException::withMessages([
                    'nuevo_stock' => 'El nuevo stock es igual al actual; no hay ajuste que registrar.',
                ]);
            }

            $reservado = (float) Reserva::query()
                ->where('articulo_id', $articulo->id)
                ->where('estado', EstadoReserva::Vigente->value)
                ->sum('cantidad');

            if ($nuevoStock < $reservado) {
                throw ValidationException::withMessages([
                    'nuevo_stock' => 'El stock no puede quedar por debajo de las unidades reservadas ('
                        .number_format($reservado, 2, ',', '.').').',
                ]);
            }

            $articulo->stock_fisico = $nuevoStock;
            $articulo->save();

            $movimiento = MovimientoDeStock::create([
                'articulo_id' => $articulo->id,
                'tipo' => TipoMovimientoStock::Ajuste->value,
                'cantidad' => $diferencia,
                'fecha' => now()->toDateString(),
                'motivo' => $motivo,
            ]);

            $porcentaje = $stockAnterior > 0
                ? round(abs($diferencia) / $stockAnterior * 100, 2)
                : 100.0;

            $this->alertas->emitirPorTipo('ajuste_significativo', [
                'articulo' => $articulo->descripcion,
                'diferencia' => $diferencia,
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $nuevoStock,
                'porcentaje' => $porcentaje,
                'articulo_id' => $articulo->id,
            ], 'ajuste:'.$movimiento->id);

            $this->auditoria->registrarEvento(
                $actor->id,
                'ajustar_inventario',
                ['stock_fisico' => $stockAnterior],
                ['stock_fisico' => $nuevoStock, 'motivo' => $motivo],
                $direccionIp,
                Articulo::class,
                $articulo->id,
            );

            return $articulo;
        });

        $this->stockMinimo->evaluar($ajustado->id);

        return $ajustado;
    }
}
