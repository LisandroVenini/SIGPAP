<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lineas_de_cotizacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->restrictOnDelete();
            $table->foreignId('articulo_id')->constrained('articulos')->restrictOnDelete();
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('cantidad_ofertada', 12, 2);
            $table->date('fecha_prometida');
            $table->boolean('adjudicada')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lineas_de_cotizacion');
    }
};
