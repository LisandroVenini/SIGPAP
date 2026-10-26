<?php

namespace App\Shared\Alertas\Services;

use App\Shared\Alertas\Models\Alerta;

class AlertaService
{
    /**
     * Persist a transversal alert. This is the only point that creates alerts.
     *
     * @param  array{descripcion: string, orden_de_produccion_id?: int|null, articulo_id?: int|null, maquinaria_id?: int|null}  $contexto
     */
    public function emitirAlerta(string $tipo, array $contexto, string $origen): Alerta
    {
        return Alerta::create([
            'tipo' => $tipo,
            'fecha' => now(),
            'descripcion' => $contexto['descripcion'],
            'origen' => $origen,
            'orden_de_produccion_id' => $contexto['orden_de_produccion_id'] ?? null,
            'articulo_id' => $contexto['articulo_id'] ?? null,
            'maquinaria_id' => $contexto['maquinaria_id'] ?? null,
        ]);
    }
}
