<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Align the authentication table with the Spanish domain language.
     */
    public function up(): void
    {
        Schema::rename('users', 'usuarios');
    }

    public function down(): void
    {
        Schema::rename('usuarios', 'users');
    }
};
