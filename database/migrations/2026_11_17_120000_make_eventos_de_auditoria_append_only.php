<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION prevent_evento_de_auditoria_mutation() RETURNS trigger AS $$
BEGIN
    RAISE EXCEPTION 'La tabla eventos_de_auditoria es append-only: no admite UPDATE ni DELETE.';
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER eventos_de_auditoria_append_only
BEFORE UPDATE OR DELETE ON eventos_de_auditoria
FOR EACH ROW EXECUTE FUNCTION prevent_evento_de_auditoria_mutation();
SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
DROP TRIGGER IF EXISTS eventos_de_auditoria_append_only ON eventos_de_auditoria;
DROP FUNCTION IF EXISTS prevent_evento_de_auditoria_mutation();
SQL);
    }
};
