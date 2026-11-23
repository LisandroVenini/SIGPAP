<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rewrite the default copy of the "calidad_baja" rule and refresh the open
 * alerts already emitted, so the readable message reaches existing rows.
 *
 * The rule message is only replaced when it still matches the old default, so
 * a personalized template is never overwritten.
 */
return new class extends Migration
{
    private const OLD_MESSAGE = 'La orden {{orden}} tiene {{rechazo_pct}}% de rechazo.';

    private const NEW_MESSAGE = '«{{producto}}» tiene {{rechazo_pct}}% de rechazo: {{rechazadas}} de {{unidades}} unidades en {{etapa}}.';

    public function up(): void
    {
        DB::table('reglas_de_alerta')
            ->where('tipo', 'calidad_baja')
            ->where('mensaje', self::OLD_MESSAGE)
            ->update(['mensaje' => self::NEW_MESSAGE]);

        $alertas = DB::table('alertas')
            ->where('tipo', 'calidad_baja')
            ->whereNull('resuelta_en')
            ->whereNotNull('regla_id')
            ->get();

        foreach ($alertas as $alerta) {
            $controlId = $this->controlIdDesdeClave($alerta->clave_dedupe);

            if ($controlId === null) {
                continue;
            }

            $control = DB::table('controles_de_calidad')
                ->leftJoin('partes_diarios', 'partes_diarios.id', '=', 'controles_de_calidad.parte_diario_id')
                ->leftJoin('etapas', 'etapas.id', '=', 'partes_diarios.etapa_id')
                ->leftJoin('ordenes_de_produccion', 'ordenes_de_produccion.id', '=', 'controles_de_calidad.orden_de_produccion_id')
                ->leftJoin('articulos', 'articulos.id', '=', 'ordenes_de_produccion.articulo_id')
                ->where('controles_de_calidad.id', $controlId)
                ->select(
                    'controles_de_calidad.orden_de_produccion_id',
                    'controles_de_calidad.cantidad_aprobada',
                    'controles_de_calidad.cantidad_rechazada',
                    'controles_de_calidad.motivo_rechazo',
                    'etapas.nombre as etapa',
                    'articulos.descripcion as producto',
                )
                ->first();

            if ($control === null) {
                continue;
            }

            $rechazadas = (float) $control->cantidad_rechazada;
            $unidades = (float) $control->cantidad_aprobada + $rechazadas;
            $rechazoPct = $unidades > 0 ? round($rechazadas / $unidades * 100, 2) : 0.0;

            $contexto = [
                'orden' => (int) $control->orden_de_produccion_id,
                'producto' => $control->producto ?? 'Orden #'.$control->orden_de_produccion_id,
                'rechazo_pct' => $rechazoPct,
                'rechazadas' => $rechazadas,
                'unidades' => $unidades,
                'etapa' => $control->etapa ?? 'la orden completa',
                'motivo' => $control->motivo_rechazo,
                'orden_de_produccion_id' => (int) $control->orden_de_produccion_id,
            ];

            $mensaje = DB::table('reglas_de_alerta')
                ->where('id', $alerta->regla_id)
                ->value('mensaje') ?? self::NEW_MESSAGE;

            DB::table('alertas')->where('id', $alerta->id)->update([
                'descripcion' => $this->renderizar((string) $mensaje, $contexto),
                'contexto' => json_encode($contexto, JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    public function down(): void
    {
        // Only the rule copy is restored: alert descriptions and contexts are a
        // historical record and are intentionally left as they were refreshed.
        DB::table('reglas_de_alerta')
            ->where('tipo', 'calidad_baja')
            ->where('mensaje', self::NEW_MESSAGE)
            ->update(['mensaje' => self::OLD_MESSAGE]);
    }

    private function controlIdDesdeClave(?string $clave): ?int
    {
        if ($clave === null) {
            return null;
        }

        $partes = explode(':', $clave);

        if (count($partes) < 2 || $partes[count($partes) - 2] !== 'calidad') {
            return null;
        }

        $ultimo = $partes[count($partes) - 1];

        return ctype_digit($ultimo) ? (int) $ultimo : null;
    }

    /**
     * @param  array<string, mixed>  $contexto
     */
    private function renderizar(string $plantilla, array $contexto): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*([a-z_]+)\s*\}\}/',
            fn (array $coincidencia): string => (string) ($contexto[$coincidencia[1]] ?? '—'),
            $plantilla,
        );
    }
};
