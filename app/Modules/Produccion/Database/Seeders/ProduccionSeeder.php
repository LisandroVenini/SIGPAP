<?php

namespace App\Modules\Produccion\Database\Seeders;

use App\Modules\Inventario\Database\Seeders\InventarioBaseSeeder;
use App\Modules\Inventario\Services\GestionArticulosService;
use App\Modules\Inventario\Services\GestionReservasService;
use App\Modules\Produccion\Enums\EstadoMaquinaria;
use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use App\Modules\Produccion\Enums\MotivoCambioPrioridad;
use App\Modules\Produccion\Models\AsignacionDeEtapa;
use App\Modules\Produccion\Models\ComponenteDeReceta;
use App\Modules\Produccion\Models\Etapa;
use App\Modules\Produccion\Models\IntervencionDeMantenimiento;
use App\Modules\Produccion\Models\Maquinaria;
use App\Modules\Produccion\Models\Operario;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Models\ParteDiario;
use App\Modules\Produccion\Models\PlanDeMantenimiento;
use App\Modules\Produccion\Models\PlanificacionDeProduccion;
use App\Modules\Produccion\Models\Receta;
use App\Modules\Produccion\Models\RegistroPrioridad;
use App\Modules\Produccion\Services\GestionCalidadService;
use App\Modules\Produccion\Services\GestionCierreService;
use App\Modules\Produccion\Services\GestionCronogramaService;
use App\Modules\Produccion\Services\GestionPartesDiariosService;
use App\Modules\Usuarios\Database\Seeders\SupervisorDemoSeeder;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\UsuariosService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Demo dataset for the production module.
 *
 * It is additive and idempotent: catalogs are matched by their natural key and
 * orders by (article, required quantity), which is unique across this dataset.
 * Nothing is deleted or truncated, so it can run on a database that already has
 * real records.
 */
class ProduccionSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('ProduccionSeeder omitido: solo corre en local/testing.');

            return;
        }

        $this->call([InventarioBaseSeeder::class, SupervisorDemoSeeder::class]);

        $actor = app(UsuariosService::class)->buscarPorEmail('supervisor.demo@sigpap.local');

        if ($actor === null) {
            $this->command?->error('No se pudo resolver el usuario demo de supervisión.');

            return;
        }

        $etapas = $this->sembrarEtapas();
        $operarios = $this->sembrarOperarios();
        $maquinarias = $this->sembrarMaquinarias();
        $planificaciones = $this->sembrarPlanificaciones();

        $productos = app(GestionArticulosService::class)->porSku(['PT-001', 'PT-002', 'PT-003', 'PT-004']);

        if (count($productos) < 4) {
            $this->command?->error('Faltan los productos del catálogo base. Revisá InventarioBaseSeeder.');

            return;
        }

        $this->sembrarHojaDeRuta($productos, $etapas);
        $this->sembrarMantenimiento($maquinarias);

        $ordenes = $this->sembrarOrdenes($productos, $planificaciones);
        $this->asignarRecursos($ordenes, $operarios, $maquinarias);
        $this->sembrarAvance($ordenes, $maquinarias, $actor);
        $this->sembrarPrioridades($ordenes, $actor);

        $this->command?->info('Producción sembrada: '.count($ordenes).' órdenes, '.count($etapas).' etapas.');
    }

    /**
     * @return array<string, Etapa>
     */
    private function sembrarEtapas(): array
    {
        $definiciones = [
            'Corte' => 'Corte de tableros y piezas según plano.',
            'Encolado' => 'Encolado y prensado de uniones.',
            'Lijado' => null,
            'Armado' => 'Ensamblado de estructura y herrajes.',
            'Tapizado' => 'Colocación de espuma y tela.',
            'Lacado' => 'Aplicación de barniz y terminación.',
        ];

        $etapas = [];

        foreach ($definiciones as $nombre => $descripcion) {
            $etapa = $this->resolverEtapa($nombre);

            if (! $etapa->exists) {
                $etapa->descripcion = $descripcion;
            }

            $etapa->save();

            if ($etapa->trashed()) {
                $etapa->restore();
            }

            $etapas[$nombre] = $etapa;
        }

        $obsoleta = $this->resolverEtapa('Etapa obsoleta');

        if (! $obsoleta->exists) {
            $obsoleta->descripcion = 'Se conserva para probar los filtros de eliminadas.';
        }

        $obsoleta->save();

        if (! $obsoleta->trashed()) {
            $obsoleta->delete();
        }

        return $etapas;
    }

    /**
     * Resolve an etapa by name ignoring case and soft deletes, because the
     * database enforces a unique index on lower(nombre).
     */
    private function resolverEtapa(string $nombre): Etapa
    {
        $etapa = Etapa::withTrashed()
            ->whereRaw('lower(nombre) = lower(?)', [$nombre])
            ->first();

        return $etapa ?? new Etapa(['nombre' => $nombre]);
    }

    /**
     * @return array<int, Operario>
     */
    private function sembrarOperarios(): array
    {
        $definiciones = [
            ['Ana', 'Gómez', 'Corte'],
            ['Bruno', 'Ferreyra', 'Armado'],
            ['Carla', 'Núñez', 'Tapizado'],
            ['Diego', 'Sosa', 'Terminación'],
            ['Elena', 'Benítez', 'Lijado'],
        ];

        $operarios = [];

        foreach ($definiciones as [$nombre, $apellido, $especialidad]) {
            $operario = Operario::withTrashed()->firstOrNew(['nombre' => $nombre, 'apellido' => $apellido]);

            if (! $operario->exists) {
                $operario->especialidad = $especialidad;
            }

            $operario->save();

            if ($operario->trashed()) {
                $operario->restore();
            }

            $operarios[] = $operario;
        }

        $inactivo = Operario::withTrashed()->firstOrNew(['nombre' => 'Franco', 'apellido' => 'Acosta']);

        if (! $inactivo->exists) {
            $inactivo->especialidad = 'Corte';
        }

        $inactivo->save();

        if (! $inactivo->trashed()) {
            $inactivo->delete();
        }

        return $operarios;
    }

    /**
     * @return array<string, Maquinaria>
     */
    private function sembrarMaquinarias(): array
    {
        $definiciones = [
            'Sierra circular' => ['tipo' => 'Corte', 'estado' => EstadoMaquinaria::Disponible, 'desgaste' => 120],
            'Lijadora orbital' => ['tipo' => 'Lijado', 'estado' => EstadoMaquinaria::Disponible, 'desgaste' => 640],
            'Encoladora' => ['tipo' => 'Encolado', 'estado' => EstadoMaquinaria::EnMantenimiento, 'desgaste' => 480],
            'Compresor de pintura' => ['tipo' => 'Lacado', 'estado' => EstadoMaquinaria::Bloqueada, 'desgaste' => 180],
        ];

        $maquinarias = [];

        foreach ($definiciones as $nombre => $datos) {
            $maquinarias[$nombre] = Maquinaria::query()->firstOrCreate(
                ['nombre' => $nombre],
                [
                    'tipo' => $datos['tipo'],
                    'estado_operativo' => $datos['estado']->value,
                    'desgaste_acumulado' => $datos['desgaste'],
                ],
            );
        }

        return $maquinarias;
    }

    /**
     * @return array<string, PlanificacionDeProduccion>
     */
    private function sembrarPlanificaciones(): array
    {
        $lunes = Carbon::now()->startOfWeek();

        $definiciones = [
            'Semana pasada' => [$lunes->copy()->subWeek(), $lunes->copy()->subDay()],
            'Semana actual' => [$lunes, $lunes->copy()->addDays(6)],
            'Semana próxima' => [$lunes->copy()->addWeek(), $lunes->copy()->addDays(13)],
            'Semana sin órdenes' => [$lunes->copy()->addWeeks(2), $lunes->copy()->addDays(20)],
        ];

        $planificaciones = [];

        foreach ($definiciones as $nombre => [$inicio, $fin]) {
            $planificaciones[$nombre] = PlanificacionDeProduccion::query()->updateOrCreate(
                ['nombre' => $nombre],
                [
                    'fecha_inicio' => $inicio->toDateString(),
                    'fecha_fin' => $fin->toDateString(),
                ],
            );
        }

        return $planificaciones;
    }

    /**
     * @param  array<string, array{id: int, codigo_sku: string, descripcion: string}>  $productos
     * @param  array<string, Etapa>  $etapas
     */
    private function sembrarHojaDeRuta(array $productos, array $etapas): void
    {
        $rutas = [
            'PT-002' => [['Corte', 8], ['Encolado', 6], ['Lijado', 5], ['Armado', 10], ['Lacado', 7]],
            'PT-001' => [['Corte', 4], ['Lijado', 3], ['Tapizado', 12], ['Armado', 6]],
            'PT-003' => [['Corte', 6], ['Armado', 8], ['Lacado', 5]],
            'PT-004' => [['Corte', 3], ['Lijado', 2]],
        ];

        $recetas = [
            'PT-002' => [['MP-002', 4], ['IN-001', 1.5], ['IN-002', 1], ['IN-003', 24], ['IN-004', 4]],
            'PT-001' => [['MP-001', 2], ['MP-003', 2.5], ['MP-004', 1], ['IN-001', 0.8], ['IN-003', 16]],
            'PT-003' => [['MP-002', 6], ['IN-001', 2], ['IN-004', 6], ['IN-003', 30]],
            'PT-004' => [['MP-001', 1.2], ['IN-002', 0.5], ['IN-003', 12]],
        ];

        $insumos = app(GestionArticulosService::class)->porSku(
            ['MP-001', 'MP-002', 'MP-003', 'MP-004', 'IN-001', 'IN-002', 'IN-003', 'IN-004'],
        );

        foreach ($rutas as $sku => $etapasDeLaRuta) {
            $producto = $productos[$sku];

            if (AsignacionDeEtapa::query()->where('articulo_id', $producto['id'])->exists()) {
                continue;
            }

            foreach ($etapasDeLaRuta as $indice => [$nombreEtapa, $minutos]) {
                AsignacionDeEtapa::query()->create([
                    'articulo_id' => $producto['id'],
                    'etapa_id' => $etapas[$nombreEtapa]->id,
                    'tiempo_por_unidad' => $minutos,
                    'orden' => $indice + 1,
                ]);
            }
        }

        foreach ($recetas as $sku => $componentes) {
            $producto = $productos[$sku];

            $receta = Receta::query()->firstOrCreate(['articulo_id' => $producto['id']]);

            if ($receta->componentesDeReceta()->exists()) {
                continue;
            }

            foreach ($componentes as [$skuInsumo, $cantidad]) {
                ComponenteDeReceta::query()->create([
                    'receta_id' => $receta->id,
                    'articulo_id' => $insumos[$skuInsumo]['id'],
                    'cantidad' => $cantidad,
                ]);
            }
        }
    }

    /**
     * @param  array<string, Maquinaria>  $maquinarias
     */
    private function sembrarMantenimiento(array $maquinarias): void
    {
        $planes = [
            ['Lijadora orbital', 500, 'Cambio de banda y revisión de rodillos.'],
            ['Sierra circular', 800, 'Afilado de disco y calibración de guías.'],
            ['Encoladora', 300, 'Limpieza de tanque y cambio de mangueras.'],
        ];

        foreach ($planes as [$nombre, $umbral, $descripcion]) {
            PlanDeMantenimiento::query()->firstOrCreate(
                ['maquinaria_id' => $maquinarias[$nombre]->id],
                ['umbral_uso' => $umbral, 'descripcion' => $descripcion],
            );
        }

        $intervenciones = [
            ['Lijadora orbital', -12, 'Preventivo', 'Taller interno', 'Cambio de banda.'],
            ['Sierra circular', -5, 'Correctivo', 'Taller externo', 'Reemplazo de rodamiento.'],
            ['Encoladora', -2, 'Preventivo', 'Taller interno', 'Limpieza profunda de tanque.'],
            ['Compresor de pintura', -20, 'Correctivo', 'Taller externo', 'Reparación de válvula.'],
        ];

        foreach ($intervenciones as [$nombre, $dias, $tipo, $responsable, $observaciones]) {
            IntervencionDeMantenimiento::query()->firstOrCreate(
                [
                    'maquinaria_id' => $maquinarias[$nombre]->id,
                    'fecha' => now()->addDays($dias)->toDateString(),
                    'tipo' => $tipo,
                ],
                ['responsable' => $responsable, 'observaciones' => $observaciones],
            );
        }
    }

    /**
     * @param  array<string, array{id: int, codigo_sku: string, descripcion: string}>  $productos
     * @param  array<string, PlanificacionDeProduccion>  $planificaciones
     * @return array<int, OrdenDeProduccion>
     */
    private function sembrarOrdenes(array $productos, array $planificaciones): array
    {
        $definiciones = [
            // [sku, planning name, final state, seed quantity, delivery days, priority, start days]
            ['PT-002', 'Semana pasada', EstadoOrdenProduccion::Cerrada, 10.0, -1, 1, -12],
            ['PT-001', 'Semana pasada', EstadoOrdenProduccion::Cerrada, 12.0, -2, 2, -11],
            ['PT-003', 'Semana pasada', EstadoOrdenProduccion::Anulada, 14.0, -3, 3, null],
            ['PT-002', 'Semana actual', EstadoOrdenProduccion::EnCurso, 16.0, 2, 1, -3],
            ['PT-001', 'Semana actual', EstadoOrdenProduccion::EnCurso, 18.0, 1, 2, -2],
            ['PT-004', 'Semana actual', EstadoOrdenProduccion::Asignada, 20.0, 4, 3, null],
            ['PT-003', 'Semana actual', EstadoOrdenProduccion::Pendiente, 22.0, 5, 4, null],
            ['PT-002', 'Semana actual', EstadoOrdenProduccion::PendientePorInsumos, 24.0, 3, 5, null],
            ['PT-001', 'Semana actual', EstadoOrdenProduccion::Pospuesta, 26.0, 9, 6, null],
            ['PT-004', 'Semana próxima', EstadoOrdenProduccion::Pendiente, 28.0, 10, 7, null],
            ['PT-002', 'Semana próxima', EstadoOrdenProduccion::Asignada, 30.0, 11, 8, null],
            ['PT-003', 'Semana próxima', EstadoOrdenProduccion::Pendiente, 32.0, 12, 9, null],
            ['PT-001', 'Semana próxima', EstadoOrdenProduccion::PendientePorInsumos, 34.0, 8, 10, null],
            ['PT-004', 'Semana próxima', EstadoOrdenProduccion::Pospuesta, 36.0, 16, 11, null],
            ['PT-004', 'Semana actual', EstadoOrdenProduccion::EnCurso, 38.0, 6, 4, -4],
        ];

        $ordenes = [];

        foreach ($definiciones as [$sku, $planificacion, $estado, $cantidad, $entrega, $prioridad, $inicio]) {
            $producto = $productos[$sku];

            // Orders that carry a full route start in progress so the daily-part
            // service can accept their parts; they are closed at the end.
            $estadoInicial = $estado === EstadoOrdenProduccion::Cerrada
                ? EstadoOrdenProduccion::EnCurso
                : $estado;

            $orden = OrdenDeProduccion::query()->firstOrCreate(
                ['articulo_id' => $producto['id'], 'cantidad_requerida' => $cantidad],
                [
                    'planificacion_de_produccion_id' => $planificaciones[$planificacion]->id,
                    'estado' => $estadoInicial->value,
                    'fecha_entrega' => now()->addDays($entrega)->toDateString(),
                    'fecha_inicio' => $inicio !== null ? now()->addDays($inicio)->toDateString() : null,
                    'fecha_prevista_inicio' => $estado === EstadoOrdenProduccion::Pospuesta
                        ? now()->addDays(6)->toDateString()
                        : null,
                    'fecha_fin' => null,
                    'cantidad_producida' => 0,
                    'prioridad_actual' => $prioridad,
                ],
            );

            $ordenes[] = $orden;
        }

        return $ordenes;
    }

    /**
     * @param  array<int, OrdenDeProduccion>  $ordenes
     * @param  array<int, Operario>  $operarios
     * @param  array<string, Maquinaria>  $maquinarias
     */
    private function asignarRecursos(array $ordenes, array $operarios, array $maquinarias): void
    {
        $asignables = [
            EstadoOrdenProduccion::Asignada->value,
            EstadoOrdenProduccion::EnCurso->value,
            EstadoOrdenProduccion::Cerrada->value,
            EstadoOrdenProduccion::Pospuesta->value,
        ];

        foreach ($ordenes as $indice => $orden) {
            if (! in_array($orden->estado, $asignables, true)) {
                continue;
            }

            $operariosDeLaOrden = [
                $operarios[$indice % count($operarios)]->id,
                $operarios[($indice + 1) % count($operarios)]->id,
            ];

            $orden->operarios()->syncWithoutDetaching(array_values(array_unique($operariosDeLaOrden)));
        }

        $conMaquinaria = [16.0, 18.0, 38.0, 20.0, 26.0];

        foreach ($ordenes as $orden) {
            if (! in_array((float) $orden->cantidad_requerida, $conMaquinaria, true)) {
                continue;
            }

            $ids = [$maquinarias['Sierra circular']->id, $maquinarias['Lijadora orbital']->id];
            $orden->maquinarias()->syncWithoutDetaching($ids);
        }
    }

    /**
     * @param  array<int, OrdenDeProduccion>  $ordenes
     * @param  array<string, Maquinaria>  $maquinarias
     */
    private function sembrarAvance(array $ordenes, array $maquinarias, Usuario $actor): void
    {
        $planes = [
            10.0 => ['completas' => 5, 'parcial' => null, 'calidadOrden' => true],
            12.0 => ['completas' => 4, 'parcial' => null, 'calidadOrden' => true],
            16.0 => ['completas' => 1, 'parcial' => 10.0, 'calidadParte' => true],
            18.0 => ['completas' => 1, 'parcial' => null, 'calidadOrden' => false],
            38.0 => ['completas' => 2, 'parcial' => null, 'calidadOrden' => true],
        ];

        $partes = app(GestionPartesDiariosService::class);
        $calidad = app(GestionCalidadService::class);
        $reservas = app(GestionReservasService::class);

        foreach ($ordenes as $orden) {
            $clave = (float) $orden->cantidad_requerida;

            if (! isset($planes[$clave])) {
                continue;
            }

            $plan = $planes[$clave];
            $orden->refresh();

            if ($orden->estado !== EstadoOrdenProduccion::EnCurso->value) {
                continue;
            }

            if (ParteDiario::query()->where('orden_de_produccion_id', $orden->id)->exists()) {
                continue;
            }

            $this->reservarInsumos($reservas, $orden);

            $etapas = AsignacionDeEtapa::query()
                ->where('articulo_id', $orden->articulo_id)
                ->ordenadas()
                ->pluck('etapa_id')
                ->values()
                ->all();

            $maquinariaId = $orden->maquinarias()->value('maquinarias.id');
            $cantidades = [];

            for ($indice = 0; $indice < $plan['completas'] && isset($etapas[$indice]); $indice++) {
                $cantidades[$indice] = (float) $orden->cantidad_requerida;
            }

            if ($plan['parcial'] !== null && isset($etapas[$plan['completas']])) {
                $cantidades[$plan['completas']] = (float) $plan['parcial'];
            }

            $primerParte = null;

            foreach ($cantidades as $indice => $cantidad) {
                $parte = $partes->registrar(
                    $actor,
                    $orden,
                    $etapas[$indice],
                    $indice === 0 ? $maquinariaId : null,
                    now()->subDays(max(1, 5 - $indice))->toDateString(),
                    $cantidad,
                    $indice === 0 ? 'Avance de corte programado.' : null,
                );

                $primerParte ??= $parte;
            }

            if (($plan['calidadParte'] ?? false) && $primerParte !== null) {
                $calidad->registrar($actor, $orden, $primerParte->id, 1.0, 'Detalle de laca en el borde.');
            }

            if (($plan['calidadOrden'] ?? false)) {
                $calidad->registrar($actor, $orden, null, 0.0, null);
            }

            $this->cerrarSiCorresponde($orden, $actor);
        }
    }

    private function reservarInsumos(GestionReservasService $reservas, OrdenDeProduccion $orden): void
    {
        $receta = Receta::query()
            ->where('articulo_id', $orden->articulo_id)
            ->with('componentesDeReceta')
            ->first();

        if ($receta === null || $receta->componentesDeReceta->isEmpty()) {
            return;
        }

        $necesidades = $receta->componentesDeReceta
            ->mapWithKeys(fn (ComponenteDeReceta $componente): array => [
                $componente->articulo_id => round((float) $componente->cantidad * (float) $orden->cantidad_requerida, 2),
            ])
            ->all();

        if ($reservas->cubierta($orden->id, $necesidades)) {
            return;
        }

        $reservas->reservar($orden->id, $necesidades);
    }

    private function cerrarSiCorresponde(OrdenDeProduccion $orden, Usuario $actor): void
    {
        $debeCerrar = in_array((float) $orden->cantidad_requerida, [10.0, 12.0], true);

        if (! $debeCerrar) {
            return;
        }

        app(GestionCierreService::class)->cerrar($actor, $orden, (float) $orden->cantidad_requerida);
    }

    /**
     * @param  array<int, OrdenDeProduccion>  $ordenes
     */
    private function sembrarPrioridades(array $ordenes, Usuario $actor): void
    {
        $reordenables = [
            14.0 => [3, 4],
            20.0 => [3, 2, 1],
            22.0 => [4, 3],
        ];

        foreach ($ordenes as $orden) {
            $clave = (float) $orden->cantidad_requerida;

            if (! isset($reordenables[$clave])) {
                continue;
            }

            $cronograma = app(GestionCronogramaService::class);

            foreach ($reordenables[$clave] as $prioridadNueva) {
                $yaRegistrada = RegistroPrioridad::query()
                    ->where('orden_de_produccion_id', $orden->id)
                    ->where('prioridad_nueva', $prioridadNueva)
                    ->exists();

                if ($yaRegistrada) {
                    continue;
                }

                $cronograma->reordenar(
                    $actor,
                    $orden,
                    $prioridadNueva,
                    MotivoCambioPrioridad::UrgenciaComercial->value,
                    'Adelanto solicitado por el cliente.',
                    true,
                );
            }
        }
    }
}
