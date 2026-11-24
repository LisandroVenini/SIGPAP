<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasas_de_rendimiento', function (Blueprint $table) {
            $table->foreignId('orden_de_produccion_id')
                ->nullable()
                ->constrained('ordenes_de_produccion')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tasas_de_rendimiento', function (Blueprint $table) {
            $table->dropConstrainedForeignId('orden_de_produccion_id');
        });
    }
};
