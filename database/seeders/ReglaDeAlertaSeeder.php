<?php

namespace Database\Seeders;

use App\Modules\Usuarios\Enums\Rol;
use App\Shared\Alertas\Catalogo\CatalogoDeAlertas;
use App\Shared\Alertas\Models\ReglaDeAlerta;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class ReglaDeAlertaSeeder extends Seeder
{
    /**
     * Demo base rules so the inbox is not empty on a fresh install.
     */
    public function run(): void
    {
        $reglas = [
            ['tipo' => 'stock_bajo_minimo', 'nombre' => 'Stock bajo el mínimo', 'condiciones' => [], 'mensaje' => '{{articulo}} está bajo el mínimo: {{disponible}} de {{minimo}}.'],
            ['tipo' => 'articulo_por_vencer', 'nombre' => 'Artículo por vencer', 'condiciones' => ['dias' => 30], 'mensaje' => '{{articulo}} vence el {{fecha_vencimiento}} ({{dias}} días).'],
            ['tipo' => 'solicitud_por_vencer', 'nombre' => 'Solicitud por vencer', 'condiciones' => ['dias' => 3], 'mensaje' => 'La solicitud {{numero}} vence el {{fecha_vencimiento}}.'],
            ['tipo' => 'cotizaciones_insuficientes', 'nombre' => 'Cotizaciones insuficientes', 'condiciones' => ['dias' => 2, 'minimo' => 3], 'mensaje' => 'La solicitud {{numero}} tiene {{recibidas}} de {{minimo}} cotizaciones.'],
            ['tipo' => 'oc_sin_confirmar', 'nombre' => 'OC sin confirmar', 'condiciones' => ['dias' => 7], 'mensaje' => 'La OC {{numero_oc}} lleva {{dias}} días sin confirmar.'],
            ['tipo' => 'orden_atrasada', 'nombre' => 'Orden atrasada', 'condiciones' => [], 'mensaje' => 'La orden {{orden}} de {{producto}} está atrasada desde el {{fecha_entrega}}.'],
            ['tipo' => 'orden_por_vencer', 'nombre' => 'Orden por vencer', 'condiciones' => ['dias' => 2, 'avance_maximo' => 100], 'mensaje' => 'La orden {{orden}} vence el {{fecha_entrega}} con {{avance}}% de avance.'],
            ['tipo' => 'calidad_baja', 'nombre' => 'Calidad baja', 'condiciones' => ['rechazo_minimo_pct' => 5], 'mensaje' => '«{{producto}}» tiene {{rechazo_pct}}% de rechazo: {{rechazadas}} de {{unidades}} unidades en {{etapa}}.'],
            ['tipo' => 'cuello_de_botella_predictivo', 'nombre' => 'Cuello de botella predictivo', 'condiciones' => [], 'mensaje' => 'La orden {{orden}} de {{producto}} se proyecta para el {{fecha_proyectada}}, {{dias_retraso}} días después de la entrega ({{fecha_entrega}}).'],
        ];

        foreach ($reglas as $definicion) {
            $tipo = CatalogoDeAlertas::obtener($definicion['tipo']);

            if ($tipo === null) {
                continue;
            }

            $regla = ReglaDeAlerta::query()->firstOrCreate(
                ['nombre' => $definicion['nombre']],
                [
                    'tipo' => $tipo->clave,
                    'modulo' => $tipo->modulo->value,
                    'severidad' => $tipo->severidadPorDefecto->value,
                    'origen_tipo' => $tipo->origen->value,
                    'condiciones' => $definicion['condiciones'],
                    'mensaje' => $definicion['mensaje'],
                    'activa' => true,
                ],
            );

            $roles = collect($tipo->rolesPorDefecto)
                ->map(fn (Rol $rol): int => Role::findByName($rol->value, 'web')->id)
                ->all();

            $regla->roles()->syncWithoutDetaching($roles);
        }
    }
}
