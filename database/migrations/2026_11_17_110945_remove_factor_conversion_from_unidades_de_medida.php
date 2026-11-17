<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unidades_de_medida', function (Blueprint $table): void {
            $table->dropColumn('factor_conversion');
        });
    }

    public function down(): void
    {
        Schema::table('unidades_de_medida', function (Blueprint $table): void {
            $table->decimal('factor_conversion', 12, 6)->default(1);
        });
    }
};
