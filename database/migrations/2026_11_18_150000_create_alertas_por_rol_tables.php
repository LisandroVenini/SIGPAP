<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reglas_de_alerta', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre')->unique();
            $table->text('descripcion')->nullable();
            $table->string('tipo');
            $table->string('modulo');
            $table->string('severidad')->default('Info');
            $table->string('origen_tipo');
            $table->jsonb('condiciones')->default('{}');
            $table->text('mensaje');
            $table->boolean('activa')->default(true);
            $table->foreignId('creada_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tipo', 'activa'], 'regla_tipo_activa_idx');
            $table->index('modulo', 'regla_modulo_idx');
        });

        Schema::create('regla_de_alerta_rol', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('regla_de_alerta_id')->constrained('reglas_de_alerta')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();

            $table->unique(['regla_de_alerta_id', 'role_id'], 'regla_rol_unique');
            $table->index('role_id', 'regla_rol_role_idx');
        });

        Schema::table('alertas', function (Blueprint $table): void {
            $table->foreignId('regla_id')->nullable()->after('id')->constrained('reglas_de_alerta')->nullOnDelete();
            $table->string('severidad')->default('Info')->after('tipo');
            $table->string('origen_tipo')->default('condicion')->after('severidad');
            $table->jsonb('contexto')->nullable()->after('descripcion');
            $table->string('clave_dedupe')->nullable()->after('contexto');
            $table->timestamp('resuelta_en')->nullable()->after('fecha');
            $table->timestamp('archivada_en')->nullable()->after('resuelta_en');
            $table->foreignId('orden_de_compra_id')->nullable()->after('maquinaria_id')->constrained('ordenes_de_compra')->nullOnDelete();
            $table->foreignId('solicitud_de_cotizacion_id')->nullable()->after('orden_de_compra_id')->constrained('solicitudes_de_cotizacion')->nullOnDelete();

            $table->index(['tipo', 'resuelta_en'], 'alerta_tipo_abierta_idx');
            $table->index('archivada_en', 'alerta_archivada_idx');
            $table->index('fecha', 'alerta_fecha_idx');
            $table->index('orden_de_compra_id', 'alerta_oc_idx');
        });

        DB::statement(
            'CREATE UNIQUE INDEX alertas_clave_dedupe_abierta_unique ON alertas (clave_dedupe) '
            .'WHERE resuelta_en IS NULL AND clave_dedupe IS NOT NULL'
        );

        Schema::create('alerta_rol', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('alerta_id')->constrained('alertas')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();

            $table->unique(['alerta_id', 'role_id'], 'alerta_rol_unique');
            $table->index('role_id', 'alerta_rol_role_idx');
        });

        Schema::create('alerta_lecturas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('alerta_id')->constrained('alertas')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->timestamp('leida_en');

            $table->unique(['alerta_id', 'usuario_id'], 'alerta_lectura_unique');
            $table->index(['usuario_id', 'leida_en'], 'alerta_lectura_usuario_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerta_lecturas');
        Schema::dropIfExists('alerta_rol');
        DB::statement('DROP INDEX IF EXISTS alertas_clave_dedupe_abierta_unique');

        Schema::table('alertas', function (Blueprint $table): void {
            $table->dropIndex('alerta_tipo_abierta_idx');
            $table->dropIndex('alerta_archivada_idx');
            $table->dropIndex('alerta_fecha_idx');
            $table->dropIndex('alerta_oc_idx');
            $table->dropConstrainedForeignId('regla_id');
            $table->dropConstrainedForeignId('orden_de_compra_id');
            $table->dropConstrainedForeignId('solicitud_de_cotizacion_id');
            $table->dropColumn(['severidad', 'origen_tipo', 'contexto', 'clave_dedupe', 'resuelta_en', 'archivada_en']);
        });

        Schema::dropIfExists('regla_de_alerta_rol');
        Schema::dropIfExists('reglas_de_alerta');
    }
};
