<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Consolidate open "calidad_baja" alerts to one per (rule, order).
 *
 * Historically every quality control emitted its own alert (dedupe key ended in
 * `calidad:<control id>`), so an order could accumulate several open alerts and
 * keep them even after closing. This migration keeps the newest alert of each
 * group, resolves the older duplicates and rewrites the kept key to the new
 * per-order reference. Alerts of orders that are already closed or annulled are
 * resolved as stale.
 */
return new class extends Migration
{
    public function up(): void
    {
        $ahora = now();

        $ordenesNoVigentes = DB::table('ordenes_de_produccion')
            ->whereIn('estado', ['Cerrada', 'Anulada'])
            ->select('id');

        DB::table('alertas')
            ->where('tipo', 'calidad_baja')
            ->whereNull('resuelta_en')
            ->whereIn('orden_de_produccion_id', $ordenesNoVigentes)
            ->update(['resuelta_en' => $ahora]);

        // An alert whose rule no longer exists has no threshold to evaluate, so
        // it can never be refreshed or resolved by the rules engine.
        DB::table('alertas')
            ->where('tipo', 'calidad_baja')
            ->whereNull('resuelta_en')
            ->whereNull('regla_id')
            ->whereNotNull('orden_de_produccion_id')
            ->update(['resuelta_en' => $ahora]);

        $abiertas = DB::table('alertas')
            ->where('tipo', 'calidad_baja')
            ->whereNull('resuelta_en')
            ->whereNotNull('regla_id')
            ->whereNotNull('orden_de_produccion_id')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        $grupos = [];

        foreach ($abiertas as $alerta) {
            $grupos[$alerta->regla_id.'|'.$alerta->orden_de_produccion_id][] = $alerta;
        }

        foreach ($grupos as $grupo) {
            $conservada = array_shift($grupo);

            foreach ($grupo as $duplicada) {
                DB::table('alertas')->where('id', $duplicada->id)->update(['resuelta_en' => $ahora]);
            }

            // The order may already have a compliant latest control (the old code
            // skipped emission when rejection was zero), so the kept alert would
            // otherwise stay open forever.
            if (! $this->ultimoControlSuperaUmbral((int) $conservada->regla_id, (int) $conservada->orden_de_produccion_id)) {
                DB::table('alertas')->where('id', $conservada->id)->update(['resuelta_en' => $ahora]);

                continue;
            }

            DB::table('alertas')->where('id', $conservada->id)->update([
                'clave_dedupe' => $conservada->regla_id.':calidad_baja:orden:'.$conservada->orden_de_produccion_id,
            ]);
        }
    }

    public function down(): void
    {
        // Irreversible on purpose: duplicates were resolved and the original
        // per-control dedupe key cannot be rebuilt from the stored context.
    }

    private function ultimoControlSuperaUmbral(int $reglaId, int $ordenId): bool
    {
        $condiciones = DB::table('reglas_de_alerta')
            ->where('id', $reglaId)
            ->whereNull('deleted_at')
            ->value('condiciones');

        if ($condiciones === null) {
            return false;
        }

        $control = DB::table('controles_de_calidad')
            ->where('orden_de_produccion_id', $ordenId)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->first();

        if ($control === null) {
            return false;
        }

        $rechazadas = (float) $control->cantidad_rechazada;
        $unidades = (float) $control->cantidad_aprobada + $rechazadas;

        if ($unidades <= 0) {
            return false;
        }

        $decodificadas = json_decode((string) $condiciones, true);
        $umbral = (float) (is_array($decodificadas) ? ($decodificadas['rechazo_minimo_pct'] ?? 5) : 5);

        return round($rechazadas / $unidades * 100, 2) >= $umbral;
    }
};
