<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('etapas', function (Blueprint $table): void {
            $table->dropUnique(['nombre']);
        });

        DB::statement('CREATE UNIQUE INDEX etapas_nombre_unique ON etapas (lower(nombre)) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS etapas_nombre_unique');

        Schema::table('etapas', function (Blueprint $table): void {
            $table->unique('nombre');
        });
    }
};
