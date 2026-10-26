<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eventos_de_auditoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->timestamp('fecha_hora');
            $table->string('direccion_ip', 45)->nullable();
            $table->string('accion');
            $table->jsonb('estado_anterior')->nullable();
            $table->jsonb('estado_nuevo')->nullable();
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id'], 'eventos_de_auditoria_auditable_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos_de_auditoria');
    }
};
