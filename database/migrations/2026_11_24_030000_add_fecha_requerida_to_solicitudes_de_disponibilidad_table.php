<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes_de_disponibilidad', function (Blueprint $table) {
            $table->date('fecha_requerida')->nullable()->after('fecha');
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes_de_disponibilidad', function (Blueprint $table) {
            $table->dropColumn('fecha_requerida');
        });
    }
};
