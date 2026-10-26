<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $table->foreignId('orden_de_compra_id')->nullable()->constrained('ordenes_de_compra')->restrictOnDelete();
            $table->string('tipo');
            $table->date('fecha');
            $table->text('detalle');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidencias');
    }
};
