<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $duplicados = DB::table('usuarios')
            ->selectRaw('lower(email) as email_normalizado')
            ->groupByRaw('lower(email)')
            ->havingRaw('count(*) > 1')
            ->pluck('email_normalizado')
            ->all();

        if ($duplicados !== []) {
            throw new RuntimeException(
                'Hay emails duplicados ignorando mayúsculas: '.implode(', ', $duplicados)
                .'. Resolvelos antes de aplicar esta migración.'
            );
        }

        DB::statement('UPDATE usuarios SET email = lower(email) WHERE email <> lower(email)');

        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS usuarios_email_lower_unique ON usuarios (lower(email))');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS usuarios_email_lower_unique');
    }
};
