<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lineas_de_solicitud_de_cotizacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_de_cotizacion_id')->constrained('solicitudes_de_cotizacion')->restrictOnDelete();
            $table->foreignId('articulo_id')->constrained('articulos')->restrictOnDelete();
            $table->decimal('cantidad_solicitada', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lineas_de_solicitud_de_cotizacion');
    }
};
