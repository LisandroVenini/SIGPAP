<?php

namespace App\Modules\Predictivo\Services;

/**
 * CU-COM-20: resolve a supplier availability answer.
 *
 * The calculation lives in the engine; this service forwards the payload and
 * turns the scenario into a readable recommendation for the person in charge.
 */
class ResolverDisponibilidadService
{
    public function __construct(private readonly MLEngine $engine) {}

    /**
     * @param  array{cantidad_disponible: float, precio_unitario: float, precio_referencia: float|null, umbral: float, fecha_prometida: string, fecha_requerida: string|null}  $respuesta
     * @return array{escenario: string, motivos: array<int, string>, desviacion_precio: float|null, dias_de_atraso: int|null, recomendacion: string}
     */
    public function resolver(array $respuesta): array
    {
        $resultado = $this->engine->resolverDisponibilidad($respuesta);

        return [
            ...$resultado,
            'recomendacion' => $this->recomendacion($resultado),
        ];
    }

    /**
     * @param  array{escenario: string, motivos: array<int, string>}  $resultado
     */
    private function recomendacion(array $resultado): string
    {
        return match ($resultado['escenario']) {
            'Sin Stock' => 'Descartar al proveedor para esta compra y consultar al siguiente del ranking.',
            'Variación Fuerte' => 'Frenar: '.$this->motivos($resultado['motivos']).'. Aprobá el nuevo costo o consultá otro proveedor.',
            default => 'Confirmar y emitir la orden de compra.',
        };
    }

    /**
     * @param  array<int, string>  $motivos
     */
    private function motivos(array $motivos): string
    {
        $partes = [];

        if (in_array('precio_sobre_umbral', $motivos, true)) {
            $partes[] = 'el precio supera el umbral esperado';
        }

        if (in_array('fecha_fuera_del_cronograma', $motivos, true)) {
            $partes[] = 'la fecha prometida no llega al cronograma';
        }

        return $partes === [] ? 'la respuesta no cumple las condiciones' : implode(' y ', $partes);
    }
}
