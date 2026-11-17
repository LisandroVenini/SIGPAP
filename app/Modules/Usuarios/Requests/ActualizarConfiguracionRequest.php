<?php

namespace App\Modules\Usuarios\Requests;

use App\Modules\Usuarios\Models\ParametroGlobal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ActualizarConfiguracionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', ParametroGlobal::class) ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'parametros' => ['required', 'array'],
            'parametros.*' => ['required', 'string'],
            'parametros.compras_ranking_peso_perfecta' => ['sometimes', 'numeric', 'min:0', 'max:1'],
            'parametros.compras_ranking_peso_retraso' => ['sometimes', 'numeric', 'min:0', 'max:1'],
            'parametros.compras_ranking_peso_faltante' => ['sometimes', 'numeric', 'min:0', 'max:1'],
            'parametros.compras_ranking_peso_rotura' => ['sometimes', 'numeric', 'min:0', 'max:1'],
            'parametros.compras_ranking_ventana_meses' => ['sometimes', 'integer', 'min:1', 'max:60'],
            'parametros.compras_multicriterio_peso_precio' => ['sometimes', 'numeric', 'min:0', 'max:1'],
            'parametros.compras_multicriterio_peso_entrega' => ['sometimes', 'numeric', 'min:0', 'max:1'],
            'parametros.compras_multicriterio_peso_confiabilidad' => ['sometimes', 'numeric', 'min:0', 'max:1'],
            'parametros.compras_multicriterio_confiabilidad_sin_historial' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'parametros.compras_multicriterio_penalizar_cobertura' => ['sometimes', 'in:0,1'],
        ];
    }

    /**
     * Both weight sets feed a weighted average, so they must add up to one.
     * The check only runs when the full set is present in the payload.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $parametros = $this->input('parametros');

            if (! is_array($parametros)) {
                return;
            }

            $this->verificarSuma($validator, $parametros, [
                'compras_ranking_peso_perfecta',
                'compras_ranking_peso_retraso',
                'compras_ranking_peso_faltante',
                'compras_ranking_peso_rotura',
            ], 'Los pesos del ranking deben sumar 1.');

            $this->verificarSuma($validator, $parametros, [
                'compras_multicriterio_peso_precio',
                'compras_multicriterio_peso_entrega',
                'compras_multicriterio_peso_confiabilidad',
            ], 'Los pesos del multicriterio deben sumar 1.');
        });
    }

    /**
     * @param  array<string, mixed>  $parametros
     * @param  array<int, string>  $claves
     */
    private function verificarSuma(
        Validator $validator,
        array $parametros,
        array $claves,
        string $mensaje,
    ): void {
        foreach ($claves as $clave) {
            if (! array_key_exists($clave, $parametros) || ! is_numeric($parametros[$clave])) {
                return;
            }
        }

        $suma = 0.0;

        foreach ($claves as $clave) {
            $suma += (float) $parametros[$clave];
        }

        if (abs($suma - 1.0) > 0.0001) {
            $validator->errors()->add('parametros', $mensaje);
        }
    }
}
