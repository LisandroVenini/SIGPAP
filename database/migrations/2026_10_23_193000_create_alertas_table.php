<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertas', function (Blueprint $table) {
            $table->id();
            $table->string('tipo');
            $table->timestamp('fecha');
            $table->text('descripcion');
            $table->string('origen')->nullable();
            $table->foreignId('orden_de_produccion_id')->nullable()->constrained('ordenes_de_produccion')->restrictOnDelete();
            $table->foreignId('articulo_id')->nullable()->constrained('articulos')->restrictOnDelete();
            $table->foreignId('maquinaria_id')->nullable()->constrained('maquinarias')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas');
    }
};
