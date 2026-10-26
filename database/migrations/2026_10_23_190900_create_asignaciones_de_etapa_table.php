<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignaciones_de_etapa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('articulo_id')->constrained('articulos')->restrictOnDelete();
            $table->foreignId('etapa_id')->constrained('etapas')->restrictOnDelete();
            $table->decimal('tiempo_por_unidad', 8, 2);
            $table->timestamps();

            $table->unique(['articulo_id', 'etapa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaciones_de_etapa');
    }
};
