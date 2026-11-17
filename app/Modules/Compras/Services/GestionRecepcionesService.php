<?php

namespace App\Modules\Compras\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Compras\Enums\EstadoOrdenDeCompra;
use App\Modules\Compras\Enums\TipoIncidencia;
use App\Modules\Compras\Models\DesempenoProveedor;
use App\Modules\Compras\Models\Incidencia;
use App\Modules\Compras\Models\LineaCotizacion;
use App\Modules\Compras\Models\LineaOrdenCompra;
use App\Modules\Compras\Models\OrdenDeCompra;
use App\Modules\Compras\Queries\RecepcionesQuery;
use App\Modules\Inventario\Services\GestionArticulosService;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionRecepcionesService
{
    public function __construct(
        private readonly AuditoriaService $auditoria,
        private readonly RecepcionesQuery $query,
        private readonly GestionArticulosService $articulos,
    ) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function ordenesRecepcionables(): Collection
    {
        $ordenes = $this->query->recepcionables();

        $resumenes = $this->articulos->resumenesDeArticulos(
            $ordenes->flatMap(fn (OrdenDeCompra $orden): Collection => $orden->lineas->pluck('articulo_id'))
                ->unique()
                ->values()
                ->all(),
        );

        return $ordenes
            ->map(fn (OrdenDeCompra $orden): array => $this->datosDeOrden($orden, $resumenes))
            ->values();
    }

    /**
     * Look up a purchase order that is still pending reception.
     *
     * @return array<string, mixed>|null
     */
    public function buscarPorNumero(string $numeroOc): ?array
    {
        $orden = OrdenDeCompra::query()
            ->with(['proveedor', 'lineas'])
            ->where('numero_oc', $numeroOc)
            ->first();

        if ($orden === null || ! $this->esRecepcionable($orden)) {
            return null;
        }

        $resumenes = $this->articulos->resumenesDeArticulos(
            $orden->lineas->pluck('articulo_id')->unique()->values()->all(),
        );

        return $this->datosDeOrden($orden, $resumenes);
    }

    /**
     * Close the reception of a purchase order: mark it as received, register the
     * supplier performance and the automatic incidencias. Stock is not touched here.
     *
     * @param  array<int, array{articulo_id: int, cantidad: float|int|string}>  $lineasRecibidas
     */
    public function registrarRecepcion(
        Usuario $actor,
        OrdenDeCompra $orden,
        bool $entregaParcial,
        array $lineasRecibidas,
        ?string $direccionIp = null,
        array $acumuladas = [],
        bool $cerrarConFaltantes = false,
    ): void {
        DB::transaction(function () use ($actor, $orden, $entregaParcial, $lineasRecibidas, $direccionIp, $acumuladas, $cerrarConFaltantes): void {
            $orden = OrdenDeCompra::query()
                ->whereKey($orden->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $this->esRecepcionable($orden)) {
                throw ValidationException::withMessages([
                    'estado' => 'La orden de compra no está pendiente de recepción.',
                ]);
            }

            $lineas = $orden->lineas()->get();
            $recibidas = $this->cantidadesRecibidas($lineasRecibidas);

            $totales = [];

            foreach ($lineas as $linea) {
                $articuloId = (int) $linea->articulo_id;
                $totales[$articuloId] = round(($acumuladas[$articuloId] ?? 0.0) + ($recibidas[$articuloId] ?? 0.0), 2);
            }

            $quedanFaltantes = $lineas->contains(
                fn ($linea): bool => ($totales[(int) $linea->articulo_id] ?? 0.0) < (float) $linea->cantidad,
            );

            if (! $cerrarConFaltantes && $quedanFaltantes !== $entregaParcial) {
                throw ValidationException::withMessages([
                    'entrega_parcial' => $quedanFaltantes
                        ? 'Quedan artículos pendientes: la entrega debe registrarse como parcial.'
                        : 'No quedan artículos pendientes: la entrega no puede registrarse como parcial.',
                ]);
            }

            $anterior = ['estado' => $orden->estado, 'entrega_parcial' => (bool) $orden->entrega_parcial];

            if ($quedanFaltantes && ! $cerrarConFaltantes) {
                $orden->update([
                    'estado' => EstadoOrdenDeCompra::Confirmada->value,
                    'entrega_parcial' => true,
                ]);

                $this->auditoria->registrarEvento(
                    $actor->id,
                    'registrar_recepcion_parcial',
                    $anterior,
                    ['estado' => EstadoOrdenDeCompra::Confirmada->value, 'entrega_parcial' => true],
                    $direccionIp,
                    OrdenDeCompra::class,
                    $orden->id,
                );

                return;
            }

            $resumenes = $this->articulos->resumenesDeArticulos(
                $lineas->pluck('articulo_id')->unique()->values()->all(),
            );

            $orden->update([
                'estado' => EstadoOrdenDeCompra::Recibida->value,
                'entrega_parcial' => $quedanFaltantes,
            ]);

            $this->registrarDesempeno($orden);

            $fechasPrometidas = $this->fechasPrometidas($orden);

            foreach ($lineas as $linea) {
                $total = $totales[(int) $linea->articulo_id] ?? 0.0;
                $descripcion = $resumenes[(int) $linea->articulo_id]['descripcion']
                    ?? 'Artículo #'.$linea->articulo_id;

                if ($total < (float) $linea->cantidad) {
                    $this->registrarIncidencia(
                        $orden,
                        TipoIncidencia::Faltante->value,
                        sprintf(
                            'Se recibieron %s de %s unidades de %s.',
                            $total,
                            (float) $linea->cantidad,
                            $descripcion,
                        ),
                    );
                }

                $prometida = $fechasPrometidas[(int) $linea->articulo_id] ?? null;

                if ($prometida !== null && today()->startOfDay()->greaterThan($prometida->copy()->startOfDay())) {
                    $this->registrarIncidencia(
                        $orden,
                        TipoIncidencia::Retraso->value,
                        sprintf(
                            'La entrega de %s estaba prometida para el %s.',
                            $descripcion,
                            $prometida->format('Y-m-d'),
                        ),
                    );
                }
            }

            $this->auditoria->registrarEvento(
                $actor->id,
                'registrar_recepcion_orden',
                $anterior,
                ['estado' => EstadoOrdenDeCompra::Recibida->value, 'entrega_parcial' => $quedanFaltantes],
                $direccionIp,
                OrdenDeCompra::class,
                $orden->id,
            );
        });
    }

    /**
     * Take the order row lock before another module reads or writes reception data,
     * so concurrent receptions of the same order serialize.
     */
    public function bloquearOrdenPorNumero(string $numeroOc): void
    {
        $orden = OrdenDeCompra::query()
            ->where('numero_oc', $numeroOc)
            ->lockForUpdate()
            ->first();

        if ($orden === null || ! $this->esRecepcionable($orden)) {
            throw ValidationException::withMessages([
                'numero_oc' => 'La orden de compra no está pendiente de recepción.',
            ]);
        }
    }

    /**
     * Minimal purchase order data by number, for other modules that need its id.
     *
     * @return array{id:int, numero_oc:string, estado:string, entrega_parcial:bool}|null
     */
    public function ordenPorNumero(string $numeroOc, bool $conLock = false): ?array
    {
        $consulta = OrdenDeCompra::query()->where('numero_oc', $numeroOc);

        if ($conLock) {
            $consulta->lockForUpdate();
        }

        $orden = $consulta->first(['id', 'numero_oc', 'estado', 'entrega_parcial']);

        return $orden === null
            ? null
            : [
                'id' => $orden->id,
                'numero_oc' => $orden->numero_oc,
                'estado' => $orden->estado,
                'entrega_parcial' => (bool) $orden->entrega_parcial,
            ];
    }

    /**
     * Resolve the numbers of several purchase orders without exposing the model.
     *
     * @param  array<int, int>  $ordenIds
     * @return array<int, string>
     */
    public function numerosDeOrden(array $ordenIds): array
    {
        if ($ordenIds === []) {
            return [];
        }

        return OrdenDeCompra::query()
            ->whereKey($ordenIds)
            ->pluck('numero_oc', 'id')
            ->mapWithKeys(fn (string $numero, int|string $id): array => [(int) $id => $numero])
            ->all();
    }

    /**
     * Undo a reception so the order can be received again.
     */
    public function revertirRecepcionPorNumero(
        Usuario $actor,
        string $numeroOc,
        ?string $direccionIp = null,
        bool $quedaRecepcionParcial = false,
    ): void {
        DB::transaction(function () use ($actor, $numeroOc, $direccionIp, $quedaRecepcionParcial): void {
            $orden = OrdenDeCompra::query()
                ->where('numero_oc', $numeroOc)
                ->lockForUpdate()
                ->first();

            $recepcionParcial = $orden !== null
                && $orden->estado === EstadoOrdenDeCompra::Confirmada->value
                && (bool) $orden->entrega_parcial;

            if ($orden === null || ($orden->estado !== EstadoOrdenDeCompra::Recibida->value && ! $recepcionParcial)) {
                throw ValidationException::withMessages([
                    'numero_oc' => 'La orden de compra no tiene una recepción registrada.',
                ]);
            }

            $anterior = ['estado' => $orden->estado, 'entrega_parcial' => (bool) $orden->entrega_parcial];

            $orden->update([
                'estado' => EstadoOrdenDeCompra::Confirmada->value,
                'entrega_parcial' => $quedaRecepcionParcial,
            ]);

            $this->registrarIncidencia(
                $orden,
                TipoIncidencia::AnulacionRecepcion->value,
                'Se anuló la recepción registrada.',
            );

            $this->auditoria->registrarEvento(
                $actor->id,
                'revertir_recepcion_orden',
                $anterior,
                ['estado' => EstadoOrdenDeCompra::Confirmada->value, 'entrega_parcial' => $quedaRecepcionParcial],
                $direccionIp,
                OrdenDeCompra::class,
                $orden->id,
            );
        });
    }

    /**
     * Close the reception by order number so another module never queries this module's model.
     *
     * @param  array<int, array{articulo_id: int, cantidad: float|int|string}>  $lineasRecibidas
     */
    public function registrarRecepcionPorNumero(
        Usuario $actor,
        string $numeroOc,
        bool $entregaParcial,
        array $lineasRecibidas,
        ?string $direccionIp = null,
        array $acumuladas = [],
        bool $cerrarConFaltantes = false,
    ): void {
        $orden = OrdenDeCompra::query()
            ->with(['proveedor', 'lineas'])
            ->where('numero_oc', $numeroOc)
            ->first();

        if ($orden === null) {
            throw ValidationException::withMessages([
                'numero_oc' => 'La orden de compra no existe.',
            ]);
        }

        $this->registrarRecepcion($actor, $orden, $entregaParcial, $lineasRecibidas, $direccionIp, $acumuladas, $cerrarConFaltantes);
    }

    private function esRecepcionable(OrdenDeCompra $orden): bool
    {
        return in_array($orden->estado, OrdenDeCompra::ESTADOS_RECEPCIONABLES, true);
    }

    /**
     * @param  array<int, array{articulo_id: int, cantidad: float|int|string}>  $lineasRecibidas
     * @return array<int, float>
     */
    private function cantidadesRecibidas(array $lineasRecibidas): array
    {
        $recibidas = [];

        foreach ($lineasRecibidas as $linea) {
            $recibidas[(int) $linea['articulo_id']] = (float) $linea['cantidad'];
        }

        return $recibidas;
    }

    private function registrarDesempeno(OrdenDeCompra $orden): void
    {
        $inicio = $orden->fecha_confirmacion ?? $orden->fecha_emision;

        DesempenoProveedor::query()->create([
            'proveedor_id' => $orden->proveedor_id,
            'orden_de_compra_id' => $orden->id,
            'tiempo_entrega_real' => (int) $inicio->startOfDay()->diffInDays(today()->startOfDay()),
            'fecha_registro' => today()->toDateString(),
        ]);
    }

    private function registrarIncidencia(OrdenDeCompra $orden, string $tipo, string $detalle): void
    {
        Incidencia::query()->create([
            'proveedor_id' => $orden->proveedor_id,
            'orden_de_compra_id' => $orden->id,
            'tipo' => $tipo,
            'fecha' => today()->toDateString(),
            'detalle' => $detalle,
        ]);
    }

    /**
     * @return array<int, Carbon>
     */
    private function fechasPrometidas(OrdenDeCompra $orden): array
    {
        if ($orden->cotizacion_id === null) {
            return [];
        }

        return LineaCotizacion::query()
            ->where('cotizacion_id', $orden->cotizacion_id)
            ->get()
            ->mapWithKeys(fn (LineaCotizacion $linea): array => [
                (int) $linea->articulo_id => $linea->fecha_prometida,
            ])
            ->all();
    }

    /**
     * @param  array<int, array{id: int, codigo_sku: string, descripcion: string, unidad: string|null}>  $resumenes
     * @return array<string, mixed>
     */
    private function datosDeOrden(OrdenDeCompra $orden, array $resumenes): array
    {
        return [
            'id' => $orden->id,
            'numero_oc' => $orden->numero_oc,
            'proveedor' => $orden->proveedor?->razon_social ?? '',
            'fecha_emision' => $orden->fecha_emision->format('Y-m-d'),
            'fecha_confirmacion' => $orden->fecha_confirmacion?->format('Y-m-d'),
            'lineas' => $orden->lineas
                ->map(fn (LineaOrdenCompra $linea): array => [
                    'articulo_id' => $linea->articulo_id,
                    'articulo' => $resumenes[(int) $linea->articulo_id] ?? [
                        'id' => $linea->articulo_id,
                        'codigo_sku' => '',
                        'descripcion' => 'Artículo #'.$linea->articulo_id,
                        'unidad' => null,
                    ],
                    'cantidad' => (float) $linea->cantidad,
                    'precio_pactado' => (float) $linea->precio_pactado,
                ])
                ->values()
                ->all(),
        ];
    }
}
