<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitud_de_cotizacion_proveedor', function (Blueprint $table) {
            $table->foreignId('solicitud_de_cotizacion_id')->constrained('solicitudes_de_cotizacion')->restrictOnDelete();
            $table->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $table->uuid('token')->nullable()->unique();
            $table->primary(['solicitud_de_cotizacion_id', 'proveedor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_de_cotizacion_proveedor');
    }
};
