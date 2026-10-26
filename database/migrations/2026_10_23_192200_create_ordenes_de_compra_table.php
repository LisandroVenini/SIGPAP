<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_de_compra', function (Blueprint $table) {
            $table->id();
            $table->string('numero_oc')->unique();
            $table->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $table->foreignId('cotizacion_id')->nullable()->constrained('cotizaciones')->restrictOnDelete();
            $table->foreignId('solicitud_de_disponibilidad_id')->nullable()->constrained('solicitudes_de_disponibilidad')->restrictOnDelete();
            $table->string('estado')->default('Emitida');
            $table->boolean('entrega_parcial')->default(false);
            $table->date('fecha_emision');
            $table->timestamps();
        });

        DB::statement(
            'ALTER TABLE ordenes_de_compra ADD CONSTRAINT ordenes_de_compra_origen_check CHECK ((cotizacion_id IS NOT NULL) <> (solicitud_de_disponibilidad_id IS NOT NULL))'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_de_compra');
    }
};
