<?php

namespace App\Modules\Produccion\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AsignarRecursosRequest extends FormRequest
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
            'operarios' => ['nullable', 'array'],
            'operarios.*' => ['integer', 'exists:operarios,id'],
            'maquinarias' => ['nullable', 'array'],
            'maquinarias.*' => ['integer', 'exists:maquinarias,id'],
        ];
    }
}
