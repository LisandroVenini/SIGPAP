<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unidades_de_medida', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->decimal('factor_conversion', 12, 6);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unidades_de_medida');
    }
};
