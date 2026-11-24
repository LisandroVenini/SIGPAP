<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The Gantt now works with the working calendar (`prod_calendario_laboral`).
 * These four shift parameters were never read by the code; they are removed so
 * the configuration screen does not show settings that do nothing.
 */
return new class extends Migration
{
    private const PARAMETROS = [
        'prod_turno_manana_inicio' => '06:00',
        'prod_turno_manana_fin' => '14:00',
        'prod_turno_tarde_inicio' => '14:00',
        'prod_turno_tarde_fin' => '22:00',
    ];

    public function up(): void
    {
        DB::table('parametros_globales')
            ->whereIn('nombre', array_keys(self::PARAMETROS))
            ->delete();
    }

    public function down(): void
    {
        foreach (self::PARAMETROS as $nombre => $valor) {
            DB::table('parametros_globales')->updateOrInsert(
                ['nombre' => $nombre],
                ['valor' => $valor, 'categoria' => 'Producción', 'updated_at' => now(), 'created_at' => now()],
            );
        }
    }
};
