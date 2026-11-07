<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('recetas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('articulo_id')->unique()->constrained('articulos')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('componentes_de_receta', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('receta_id')->constrained('recetas')->cascadeOnDelete();
            $table->foreignId('articulo_id')->constrained('articulos')->restrictOnDelete();
            $table->decimal('cantidad', 12, 2);
            $table->timestamps();

            $table->unique(['receta_id', 'articulo_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('componentes_de_receta');
        Schema::dropIfExists('recetas');
    }
};
