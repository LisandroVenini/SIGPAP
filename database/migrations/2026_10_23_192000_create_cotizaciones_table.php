<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_de_cotizacion_id')->nullable()->constrained('solicitudes_de_cotizacion')->restrictOnDelete();
            $table->foreignId('solicitud_de_disponibilidad_id')->nullable()->constrained('solicitudes_de_disponibilidad')->restrictOnDelete();
            $table->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $table->date('fecha');
            $table->timestamps();
        });

        DB::statement(
            'ALTER TABLE cotizaciones ADD CONSTRAINT cotizaciones_origen_check CHECK ((solicitud_de_cotizacion_id IS NOT NULL) <> (solicitud_de_disponibilidad_id IS NOT NULL))'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizaciones');
    }
};
