<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_de_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('articulo_id')->constrained('articulos')->restrictOnDelete();
            $table->foreignId('orden_de_produccion_id')->nullable()->constrained('ordenes_de_produccion')->restrictOnDelete();
            $table->foreignId('orden_de_compra_id')->nullable()->constrained('ordenes_de_compra')->restrictOnDelete();
            $table->string('tipo');
            $table->decimal('cantidad', 12, 2);
            $table->date('fecha');
            $table->string('motivo')->nullable();
            $table->string('numero_remito')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_de_stock');
    }
};
