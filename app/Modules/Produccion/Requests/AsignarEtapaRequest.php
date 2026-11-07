<?php

namespace App\Modules\Produccion\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AsignarEtapaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('produccion.gestionar') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'etapa_id' => ['required', 'integer', 'exists:etapas,id'],
            'tiempo_por_unidad' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
