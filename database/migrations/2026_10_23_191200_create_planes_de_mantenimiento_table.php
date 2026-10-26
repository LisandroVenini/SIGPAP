<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planes_de_mantenimiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maquinaria_id')->constrained('maquinarias')->restrictOnDelete();
            $table->decimal('umbral_uso', 12, 2);
            $table->text('descripcion');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planes_de_mantenimiento');
    }
};
