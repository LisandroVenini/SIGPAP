<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asignaciones_de_etapa', function (Blueprint $table): void {
            $table->unsignedSmallInteger('orden')->nullable()->after('tiempo_por_unidad');
        });

        DB::statement(<<<'SQL'
            WITH ranked AS (
                SELECT id, ROW_NUMBER() OVER (PARTITION BY articulo_id ORDER BY id) AS orden
                FROM asignaciones_de_etapa
            )
            UPDATE asignaciones_de_etapa AS ae
            SET orden = ranked.orden
            FROM ranked
            WHERE ae.id = ranked.id
        SQL);

        Schema::table('asignaciones_de_etapa', function (Blueprint $table): void {
            $table->unsignedSmallInteger('orden')->default(1)->nullable(false)->change();
            $table->unique(['articulo_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::table('asignaciones_de_etapa', function (Blueprint $table): void {
            $table->dropUnique(['articulo_id', 'orden']);
            $table->dropColumn('orden');
        });
    }
};
