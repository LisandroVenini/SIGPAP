<?php

namespace App\Modules\Compras\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\UsuariosService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionProveedoresService
{
    private const ESTADO_ACTIVO = 'Activo';

    private const ESTADO_INACTIVO = 'Inactivo';

    public function __construct(
        private readonly AuditoriaService $auditoria,
        private readonly UsuariosService $usuarios,
    ) {}

    public function puedeGestionar(Usuario $actor): bool
    {
        return $this->usuarios->autorizar($actor, 'compras.gestionar');
    }

    /**
     * @param  array{buscar?: string|null}  $filtros
     */
    public function listar(array $filtros = []): LengthAwarePaginator
    {
        return Proveedor::query()
            ->withCount('solicitudesDeCotizacion')
            ->withCount('ordenesDeCompra')
            ->when($filtros['buscar'] ?? null, fn ($query, string $buscar) => $query->buscar($buscar))
            ->orderBy('razon_social')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Proveedor $proveedor) => [
                'id' => $proveedor->id,
                'razon_social' => $proveedor->razon_social,
                'cuit' => $proveedor->cuit,
                'email' => $proveedor->email,
                'telefono' => $proveedor->telefono,
                'estado' => $proveedor->estado,
                'solicitudes' => $proveedor->solicitudes_de_cotizacion_count,
                'ordenes' => $proveedor->ordenes_de_compra_count,
            ]);
    }

    /**
     * @param  array{razon_social: string, cuit: string, email?: string|null, telefono?: string|null}  $datos
     */
    public function crear(Usuario $actor, array $datos, ?string $direccionIp = null): Proveedor
    {
        return DB::transaction(function () use ($actor, $datos, $direccionIp): Proveedor {
            $this->validarCuitDisponible($datos['cuit']);
            $this->validarEmailDisponible($datos['email'] ?? null);

            $proveedor = Proveedor::create([
                'razon_social' => $datos['razon_social'],
                'cuit' => $datos['cuit'],
                'email' => $datos['email'] ?? null,
                'telefono' => $datos['telefono'] ?? null,
                'estado' => self::ESTADO_ACTIVO,
            ]);

            $this->auditoria->registrarEvento(
                $actor->id,
                'crear_proveedor',
                null,
                [
                    'razon_social' => $proveedor->razon_social,
                    'cuit' => $proveedor->cuit,
                    'email' => $proveedor->email,
                    'telefono' => $proveedor->telefono,
                ],
                $direccionIp,
                Proveedor::class,
                $proveedor->id,
            );

            return $proveedor;
        });
    }

    /**
     * The CUIT is immutable: any value present in the payload is ignored.
     *
     * @param  array{razon_social: string, email?: string|null, telefono?: string|null, cuit?: string}  $datos
     */
    public function actualizar(Usuario $actor, Proveedor $proveedor, array $datos, ?string $direccionIp = null): Proveedor
    {
        return DB::transaction(function () use ($actor, $proveedor, $datos, $direccionIp): Proveedor {
            $this->validarEmailDisponible($datos['email'] ?? null, $proveedor->id);

            $anterior = [
                'razon_social' => $proveedor->razon_social,
                'email' => $proveedor->email,
                'telefono' => $proveedor->telefono,
            ];

            $proveedor->update([
                'razon_social' => $datos['razon_social'],
                'email' => array_key_exists('email', $datos) ? $datos['email'] : $proveedor->email,
                'telefono' => array_key_exists('telefono', $datos) ? $datos['telefono'] : $proveedor->telefono,
            ]);

            $nuevo = [
                'razon_social' => $proveedor->razon_social,
                'email' => $proveedor->email,
                'telefono' => $proveedor->telefono,
            ];

            $this->auditoria->registrarEvento(
                $actor->id,
                'actualizar_proveedor',
                $anterior,
                $nuevo,
                $direccionIp,
                Proveedor::class,
                $proveedor->id,
            );

            return $proveedor;
        });
    }

    public function darDeBaja(Usuario $actor, Proveedor $proveedor, ?string $direccionIp = null): void
    {
        if ($proveedor->estado === self::ESTADO_INACTIVO) {
            return;
        }

        DB::transaction(function () use ($actor, $proveedor, $direccionIp): void {
            $anterior = ['estado' => $proveedor->estado];

            $proveedor->update(['estado' => self::ESTADO_INACTIVO]);

            $this->auditoria->registrarEvento(
                $actor->id,
                'dar_de_baja_proveedor',
                $anterior,
                ['estado' => self::ESTADO_INACTIVO],
                $direccionIp,
                Proveedor::class,
                $proveedor->id,
            );
        });
    }

    private function validarCuitDisponible(string $cuit): void
    {
        if (Proveedor::query()->where('cuit', $cuit)->exists()) {
            throw ValidationException::withMessages([
                'cuit' => 'El CUIT ya está registrado.',
            ]);
        }
    }

    private function validarEmailDisponible(?string $email, ?int $ignorarId = null): void
    {
        if ($email === null || $email === '') {
            return;
        }

        $existe = Proveedor::query()
            ->where('email', $email)
            ->when($ignorarId !== null, fn ($query) => $query->where('id', '!=', $ignorarId))
            ->exists();

        if ($existe) {
            throw ValidationException::withMessages([
                'email' => 'El email ya está registrado.',
            ]);
        }
    }
}
