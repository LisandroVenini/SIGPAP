<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partes_diarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_de_produccion_id')->constrained('ordenes_de_produccion')->restrictOnDelete();
            $table->foreignId('etapa_id')->constrained('etapas')->restrictOnDelete();
            $table->foreignId('maquinaria_id')->nullable()->constrained('maquinarias')->restrictOnDelete();
            $table->date('fecha');
            $table->decimal('cantidad_conformada', 12, 2);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['orden_de_produccion_id', 'etapa_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partes_diarios');
    }
};
