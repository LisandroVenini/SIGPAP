<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lineas_base_de_cronograma', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_de_produccion_id')->constrained('ordenes_de_produccion')->cascadeOnDelete();
            $table->foreignId('etapa_id')->constrained('etapas')->restrictOnDelete();
            $table->dateTime('inicio_plan');
            $table->dateTime('fin_plan');
            $table->timestamps();

            $table->unique(['orden_de_produccion_id', 'etapa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lineas_base_de_cronograma');
    }
};
