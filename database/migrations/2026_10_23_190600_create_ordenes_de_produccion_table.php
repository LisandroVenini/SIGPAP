<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_de_produccion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('articulo_id')->constrained('articulos')->restrictOnDelete();
            $table->foreignId('planificacion_de_produccion_id')->constrained('planificaciones_de_produccion')->restrictOnDelete();
            $table->string('estado')->default('Pendiente');
            $table->decimal('cantidad_requerida', 12, 2);
            $table->date('fecha_entrega');
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->decimal('cantidad_producida', 12, 2)->default(0);
            $table->integer('prioridad_actual')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_de_produccion');
    }
};
