<?php

namespace App\Modules\Inventario\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Inventario\Enums\TipoMovimientoStock;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Models\MovimientoDeStock;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionAjustesService
{
    public function __construct(private readonly AuditoriaService $auditoria) {}

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

        return DB::transaction(function () use ($actor, $articulo, $nuevoStock, $motivo, $direccionIp): Articulo {
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

            $articulo->stock_fisico = $nuevoStock;
            $articulo->save();

            MovimientoDeStock::create([
                'articulo_id' => $articulo->id,
                'tipo' => TipoMovimientoStock::Ajuste->value,
                'cantidad' => $diferencia,
                'fecha' => now()->toDateString(),
                'motivo' => $motivo,
            ]);

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
    }
}
