<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lineas_de_orden_de_compra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_de_compra_id')->constrained('ordenes_de_compra')->restrictOnDelete();
            $table->foreignId('articulo_id')->constrained('articulos')->restrictOnDelete();
            $table->decimal('cantidad', 12, 2);
            $table->decimal('precio_pactado', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lineas_de_orden_de_compra');
    }
};
