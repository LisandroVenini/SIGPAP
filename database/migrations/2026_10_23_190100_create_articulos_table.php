<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articulos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_sku')->unique();
            $table->string('descripcion');
            $table->string('tipo');
            $table->string('origen')->nullable();
            $table->decimal('stock_fisico', 12, 2)->default(0);
            $table->decimal('stock_minimo', 12, 2)->nullable();
            $table->decimal('stock_maximo', 12, 2)->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->string('estado')->default('Activo');
            $table->foreignId('unidad_de_medida_id')->constrained('unidades_de_medida')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articulos');
    }
};
