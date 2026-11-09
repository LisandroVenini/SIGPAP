<?php

namespace App\Modules\Usuarios\Requests;

use App\Modules\Usuarios\Models\ParametroGlobal;
use Illuminate\Foundation\Http\FormRequest;

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
}
