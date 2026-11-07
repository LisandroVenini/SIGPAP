<?php

namespace App\Modules\Compras\Services;

use App\Modules\Auditoria\Services\AuditoriaService;
use App\Modules\Compras\Mail\SolicitudDeCotizacionMail;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Models\SolicitudDeCotizacion;
use App\Modules\Compras\Queries\SolicitudesDeCotizacionQuery;
use App\Modules\Inventario\Services\GestionArticulosService;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\UsuariosService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GestionSolicitudesDeCotizacionService
{
    private const ESTADO_ENVIADA = 'Enviada';

    private const ESTADO_ANULADA = 'Anulada';

    private const ESTADO_ADJUDICADA = 'Adjudicada';

    public function __construct(
        private readonly AuditoriaService $auditoria,
        private readonly UsuariosService $usuarios,
        private readonly GestionArticulosService $articulos,
        private readonly SolicitudesDeCotizacionQuery $query,
    ) {}

    public function puedeGestionar(Usuario $actor): bool
    {
        return $this->usuarios->autorizar($actor, 'compras.gestionar');
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function listar(array $filtros = []): LengthAwarePaginator
    {
        return $this->query->listar($filtros);
    }

    /**
     * @return Collection<int, array{id: int, razon_social: string}>
     */
    public function proveedoresActivos(): Collection
    {
        return Proveedor::query()
            ->where('estado', 'Activo')
            ->orderBy('razon_social')
            ->get(['id', 'razon_social'])
            ->map(fn (Proveedor $proveedor): array => [
                'id' => $proveedor->id,
                'razon_social' => $proveedor->razon_social,
            ]);
    }

    /**
     * @param  array{fecha_vencimiento: string, lineas: array<int, array{articulo_id: int, cantidad_solicitada: float|int|string}>, proveedores: array<int, int>}  $datos
     */
    public function crear(Usuario $actor, array $datos, ?string $direccionIp = null): SolicitudDeCotizacion
    {
        [$solicitud, $enviosPendientes] = DB::transaction(function () use ($actor, $datos, $direccionIp): array {
            $proveedores = $this->proveedoresConEmail($datos['proveedores']);

            $solicitud = SolicitudDeCotizacion::create([
                'fecha' => today()->toDateString(),
                'fecha_vencimiento' => $datos['fecha_vencimiento'],
                'estado' => self::ESTADO_ENVIADA,
            ]);

            $this->reemplazarLineas($solicitud, $datos['lineas']);

            $tokens = $this->sincronizarProveedores($solicitud, $datos['proveedores']);

            $this->auditoria->registrarEvento(
                $actor->id,
                'crear_solicitud_cotizacion',
                null,
                [
                    'fecha_vencimiento' => $datos['fecha_vencimiento'],
                    'lineas' => count($datos['lineas']),
                    'proveedores' => array_values($datos['proveedores']),
                ],
                $direccionIp,
                SolicitudDeCotizacion::class,
                $solicitud->id,
            );

            $enviosPendientes = $proveedores
                ->map(fn (Proveedor $proveedor): array => [
                    'proveedor' => $proveedor,
                    'token' => $tokens[$proveedor->id],
                ])
                ->values()
                ->all();

            return [$solicitud, $enviosPendientes];
        });

        $this->enviarInvitaciones($solicitud, $enviosPendientes);

        return $solicitud;
    }

    /**
     * @param  array{fecha_vencimiento: string, lineas: array<int, array{articulo_id: int, cantidad_solicitada: float|int|string}>, proveedores: array<int, int>}  $datos
     */
    public function actualizar(Usuario $actor, SolicitudDeCotizacion $solicitud, array $datos, ?string $direccionIp = null): void
    {
        [$solicitud, $enviosPendientes] = DB::transaction(function () use ($actor, $solicitud, $datos, $direccionIp): array {
            $solicitud = SolicitudDeCotizacion::query()
                ->whereKey($solicitud->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($solicitud->cotizaciones()->exists()) {
                throw ValidationException::withMessages([
                    'solicitud' => 'La solicitud ya tiene cotizaciones cargadas y no puede modificarse.',
                ]);
            }

            $proveedoresPrevios = $solicitud->proveedores()->pluck('proveedores.id')->all();
            $proveedoresNuevosIds = array_values(array_diff($datos['proveedores'], $proveedoresPrevios));
            $proveedoresNuevos = $this->proveedoresConEmail($proveedoresNuevosIds);

            $anterior = [
                'fecha_vencimiento' => $solicitud->fecha_vencimiento->format('Y-m-d'),
                'lineas' => $solicitud->lineas()->count(),
                'proveedores' => $proveedoresPrevios,
            ];

            $solicitud->update([
                'fecha_vencimiento' => $datos['fecha_vencimiento'],
            ]);

            $solicitud->lineas()->delete();
            $this->reemplazarLineas($solicitud, $datos['lineas']);

            $tokens = $this->sincronizarProveedores($solicitud, $datos['proveedores']);

            $this->auditoria->registrarEvento(
                $actor->id,
                'actualizar_solicitud_cotizacion',
                $anterior,
                [
                    'fecha_vencimiento' => $datos['fecha_vencimiento'],
                    'lineas' => count($datos['lineas']),
                    'proveedores' => array_values($datos['proveedores']),
                ],
                $direccionIp,
                SolicitudDeCotizacion::class,
                $solicitud->id,
            );

            $enviosPendientes = $proveedoresNuevos
                ->map(fn (Proveedor $proveedor): array => [
                    'proveedor' => $proveedor,
                    'token' => $tokens[$proveedor->id],
                ])
                ->values()
                ->all();

            return [$solicitud, $enviosPendientes];
        });

        $this->enviarInvitaciones($solicitud, $enviosPendientes);
    }

    public function anular(Usuario $actor, SolicitudDeCotizacion $solicitud, ?string $motivo = null, ?string $direccionIp = null): void
    {
        DB::transaction(function () use ($actor, $solicitud, $motivo, $direccionIp): void {
            $solicitud = SolicitudDeCotizacion::query()
                ->whereKey($solicitud->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($solicitud->estado, [self::ESTADO_ADJUDICADA, self::ESTADO_ANULADA], true)) {
                throw ValidationException::withMessages([
                    'estado' => 'La solicitud no puede anularse en su estado actual.',
                ]);
            }

            $anterior = ['estado' => $solicitud->estado];

            $solicitud->update(['estado' => self::ESTADO_ANULADA]);

            $this->auditoria->registrarEvento(
                $actor->id,
                'anular_solicitud_cotizacion',
                $anterior,
                ['estado' => self::ESTADO_ANULADA, 'motivo' => $motivo],
                $direccionIp,
                SolicitudDeCotizacion::class,
                $solicitud->id,
            );
        });
    }

    /**
     * @param  array<int, array{articulo_id: int, cantidad_solicitada: float|int|string}>  $lineas
     */
    private function reemplazarLineas(SolicitudDeCotizacion $solicitud, array $lineas): void
    {
        foreach ($lineas as $linea) {
            $solicitud->lineas()->create([
                'articulo_id' => $linea['articulo_id'],
                'cantidad_solicitada' => $linea['cantidad_solicitada'],
            ]);
        }
    }

    /**
     * @param  array<int, int>  $proveedores
     * @return array<int, string> token keyed by provider id
     */
    private function sincronizarProveedores(SolicitudDeCotizacion $solicitud, array $proveedores): array
    {
        $existentes = $solicitud->proveedores()->get()
            ->mapWithKeys(fn (Proveedor $proveedor): array => [$proveedor->id => $proveedor->pivot->token])
            ->all();

        $tokensNuevos = [];
        $sincronizar = [];

        foreach ($proveedores as $proveedorId) {
            $token = $existentes[$proveedorId] ?? null;

            if ($token === null) {
                $token = (string) Str::uuid();
                $tokensNuevos[$proveedorId] = $token;
            }

            $sincronizar[$proveedorId] = ['token' => $token];
        }

        $solicitud->proveedores()->sync($sincronizar);

        // Only providers that did not have a link yet are returned, so the caller mails just those.
        return $tokensNuevos;
    }

    /**
     * @param  array<int, int>  $ids
     * @return Collection<int, Proveedor>
     */
    private function proveedoresConEmail(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        $proveedores = Proveedor::query()->whereKey($ids)->get();

        if ($proveedores->count() !== count(array_unique($ids))) {
            throw ValidationException::withMessages([
                'proveedores' => 'Alguno de los proveedores seleccionados no existe.',
            ]);
        }

        $sinEmail = $proveedores->first(fn (Proveedor $proveedor): bool => blank($proveedor->email));

        if ($sinEmail !== null) {
            throw ValidationException::withMessages([
                'proveedores' => "El proveedor {$sinEmail->razon_social} no tiene un email registrado.",
            ]);
        }

        return $proveedores->keyBy('id');
    }

    /**
     * @param  array<int, array{proveedor: Proveedor, token: string}>  $envios
     */
    private function enviarInvitaciones(SolicitudDeCotizacion $solicitud, array $envios): void
    {
        if ($envios === []) {
            return;
        }

        $solicitud->loadMissing('lineas');

        $resumenes = $this->articulos->resumenesDeArticulos(
            $solicitud->lineas->pluck('articulo_id')->all(),
        );

        $lineas = $solicitud->lineas
            ->map(fn ($linea): array => [
                'articulo' => $resumenes[$linea->articulo_id]['descripcion'] ?? 'Artículo #'.$linea->articulo_id,
                'cantidad_solicitada' => (float) $linea->cantidad_solicitada,
            ])
            ->values()
            ->all();

        foreach ($envios as $envio) {
            Mail::to($envio['proveedor']->email)->send(
                new SolicitudDeCotizacionMail(
                    $solicitud,
                    $envio['proveedor'],
                    $envio['token'],
                    $lineas,
                ),
            );
        }
    }
}
