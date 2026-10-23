<?php

namespace Database\Factories;

use App\Modules\Auditoria\Models\EventoDeAuditoria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventoDeAuditoria>
 */
class EventoDeAuditoriaFactory extends Factory
{
    /**
     * @var class-string<EventoDeAuditoria>
     */
    protected $model = EventoDeAuditoria::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_id' => UsuarioFactory::new(),
            'auditable_type' => null,
            'auditable_id' => null,
            'fecha_hora' => now(),
            'direccion_ip' => '127.0.0.1',
            'accion' => 'accion de prueba',
            'estado_anterior' => null,
            'estado_nuevo' => null,
        ];
    }
}
