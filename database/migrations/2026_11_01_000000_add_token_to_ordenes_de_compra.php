<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes_de_compra', function (Blueprint $table) {
            $table->uuid('token')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('ordenes_de_compra', function (Blueprint $table) {
            $table->dropUnique(['token']);
            $table->dropColumn('token');
        });
    }
};
