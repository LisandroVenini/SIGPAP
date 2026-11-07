<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('controles_de_calidad', function (Blueprint $table): void {
            $table->foreignId('parte_diario_id')
                ->nullable()
                ->after('orden_de_produccion_id')
                ->constrained('partes_diarios')
                ->nullOnDelete();
        });

        Schema::table('ordenes_de_produccion', function (Blueprint $table): void {
            $table->date('fecha_prevista_inicio')
                ->nullable()
                ->after('fecha_inicio');
        });
    }

    public function down(): void
    {
        Schema::table('ordenes_de_produccion', function (Blueprint $table): void {
            $table->dropColumn('fecha_prevista_inicio');
        });

        Schema::table('controles_de_calidad', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('parte_diario_id');
        });
    }
};
