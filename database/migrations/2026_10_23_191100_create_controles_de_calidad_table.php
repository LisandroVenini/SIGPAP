<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('controles_de_calidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_de_produccion_id')->constrained('ordenes_de_produccion')->restrictOnDelete();
            $table->date('fecha');
            $table->decimal('cantidad_aprobada', 12, 2);
            $table->decimal('cantidad_rechazada', 12, 2);
            $table->text('motivo_rechazo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('controles_de_calidad');
    }
};
