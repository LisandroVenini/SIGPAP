<?php

namespace App\Modules\Produccion\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('plan')) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'maquinaria_id' => ['required', 'integer', 'exists:maquinarias,id'],
            'umbral_uso' => ['required', 'numeric', 'gt:0'],
            'descripcion' => ['required', 'string', 'max:1000'],
        ];
    }
}
