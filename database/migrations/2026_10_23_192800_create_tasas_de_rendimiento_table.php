<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasas_de_rendimiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etapa_id')->constrained('etapas')->restrictOnDelete();
            $table->decimal('valor', 12, 4);
            $table->decimal('desviacion', 12, 4);
            $table->date('fecha_calculo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasas_de_rendimiento');
    }
};
