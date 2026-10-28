<?php

namespace App\Modules\Inventario\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Inventario\Enums\EstadoArticulo;
use App\Modules\Inventario\Enums\OrigenArticulo;
use App\Modules\Inventario\Enums\TipoArticulo;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Models\UnidadDeMedida;
use App\Modules\Inventario\Queries\ArticulosQuery;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\UsuariosService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionArticulosService
{
    public function __construct(
        private readonly AuditoriaService $auditoria,
        private readonly ArticulosQuery $query,
        private readonly UsuariosService $usuarios,
    ) {}

    public function puedeGestionar(Usuario $actor): bool
    {
        return $this->usuarios->autorizar($actor, 'inventario.gestionar');
    }

    /**
     * @param  array{buscar?: string|null, tipo?: string|null, estado?: string|null}  $filtros
     */
    public function listar(array $filtros = []): LengthAwarePaginator
    {
        return $this->query->listar($filtros);
    }

    /**
     * @return array{total: int, activos: int, inactivos: int}
     */
    public function resumen(): array
    {
        return $this->query->resumen();
    }

    /**
     * @return Collection<int, UnidadDeMedida>
     */
    public function unidades(): Collection
    {
        return UnidadDeMedida::query()->orderBy('nombre')->get(['id', 'nombre']);
    }

    /**
     * @param  array{codigo_sku: string, descripcion: string, tipo: string, origen?: string|null, unidad_de_medida_id: int, stock_minimo?: float|string|null, stock_maximo?: float|string|null}  $datos
     */
    public function crear(Usuario $actor, array $datos, ?string $direccionIp = null): Articulo
    {
        return DB::transaction(function () use ($actor, $datos, $direccionIp): Articulo {
            $articulo = Articulo::create([
                'codigo_sku' => $datos['codigo_sku'],
                'descripcion' => $datos['descripcion'],
                'tipo' => $datos['tipo'],
                'origen' => $this->origenPara($datos),
                'unidad_de_medida_id' => $datos['unidad_de_medida_id'],
                'stock_fisico' => 0,
                'stock_minimo' => $datos['stock_minimo'] ?? null,
                'stock_maximo' => $datos['stock_maximo'] ?? null,
                'estado' => EstadoArticulo::Activo->value,
            ]);

            $this->auditoria->registrarEvento(
                $actor->id,
                'crear_articulo',
                null,
                ['codigo_sku' => $articulo->codigo_sku, 'tipo' => $articulo->tipo],
                $direccionIp,
                Articulo::class,
                $articulo->id,
            );

            return $articulo;
        });
    }

    /**
     * @param  array{descripcion: string, tipo: string, origen?: string|null, unidad_de_medida_id: int, stock_minimo?: float|string|null, stock_maximo?: float|string|null}  $datos
     */
    public function actualizar(Usuario $actor, Articulo $articulo, array $datos, ?string $direccionIp = null): Articulo
    {
        return DB::transaction(function () use ($actor, $articulo, $datos, $direccionIp): Articulo {
            $anterior = [
                'descripcion' => $articulo->descripcion,
                'tipo' => $articulo->tipo,
                'origen' => $articulo->origen,
                'unidad_de_medida_id' => $articulo->unidad_de_medida_id,
                'stock_minimo' => $articulo->stock_minimo,
                'stock_maximo' => $articulo->stock_maximo,
            ];

            $articulo->update([
                'descripcion' => $datos['descripcion'],
                'tipo' => $datos['tipo'],
                'origen' => $this->origenPara($datos),
                'unidad_de_medida_id' => $datos['unidad_de_medida_id'],
                'stock_minimo' => $datos['stock_minimo'] ?? null,
                'stock_maximo' => $datos['stock_maximo'] ?? null,
            ]);

            $this->auditoria->registrarEvento(
                $actor->id,
                'actualizar_articulo',
                $anterior,
                [
                    'descripcion' => $articulo->descripcion,
                    'tipo' => $articulo->tipo,
                    'origen' => $articulo->origen,
                    'unidad_de_medida_id' => $articulo->unidad_de_medida_id,
                    'stock_minimo' => $articulo->stock_minimo,
                    'stock_maximo' => $articulo->stock_maximo,
                ],
                $direccionIp,
                Articulo::class,
                $articulo->id,
            );

            return $articulo;
        });
    }

    public function cambiarEstado(Usuario $actor, Articulo $articulo, bool $confirmar = false, ?string $direccionIp = null): Articulo
    {
        return DB::transaction(function () use ($actor, $articulo, $confirmar, $direccionIp): Articulo {
            $articulo = Articulo::query()
                ->whereKey($articulo->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $nuevoEstado = $articulo->estado === EstadoArticulo::Activo->value
                ? EstadoArticulo::Inactivo->value
                : EstadoArticulo::Activo->value;

            if ($nuevoEstado === EstadoArticulo::Inactivo->value && (float) $articulo->stock_fisico > 0 && ! $confirmar) {
                throw ValidationException::withMessages([
                    'confirmar' => 'El artículo tiene stock físico. Confirmá la baja para continuar.',
                ]);
            }

            $anterior = ['estado' => $articulo->estado];

            $articulo->estado = $nuevoEstado;
            $articulo->save();

            $this->auditoria->registrarEvento(
                $actor->id,
                'cambiar_estado_articulo',
                $anterior,
                ['estado' => $articulo->estado],
                $direccionIp,
                Articulo::class,
                $articulo->id,
            );

            return $articulo;
        });
    }

    /**
     * @param  array{tipo: string, origen?: string|null}  $datos
     */
    private function origenPara(array $datos): ?string
    {
        if ($datos['tipo'] !== TipoArticulo::ProductoTerminado->value) {
            return null;
        }

        $origen = $datos['origen'] ?? null;

        return $origen !== null && in_array($origen, OrigenArticulo::values(), true)
            ? $origen
            : null;
    }
}
