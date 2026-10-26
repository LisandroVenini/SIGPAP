<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_de_disponibilidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $table->foreignId('articulo_id')->constrained('articulos')->restrictOnDelete();
            $table->date('fecha');
            $table->decimal('cantidad_solicitada', 12, 2);
            $table->string('estado')->default('Enviada');
            $table->uuid('token')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_de_disponibilidad');
    }
};
