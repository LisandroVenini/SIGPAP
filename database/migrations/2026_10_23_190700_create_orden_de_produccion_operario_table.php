<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orden_de_produccion_operario', function (Blueprint $table) {
            $table->foreignId('orden_de_produccion_id')->constrained('ordenes_de_produccion')->restrictOnDelete();
            $table->foreignId('operario_id')->constrained('operarios')->restrictOnDelete();
            $table->primary(['orden_de_produccion_id', 'operario_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_de_produccion_operario');
    }
};
