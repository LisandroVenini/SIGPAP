<?php

namespace App\Modules\Produccion\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'operarios.*' => [
                'integer',
                'distinct',
                Rule::exists('operarios', 'id')->whereNull('deleted_at'),
            ],
            'maquinarias' => ['nullable', 'array'],
            'maquinarias.*' => ['integer', 'distinct', 'exists:maquinarias,id'],
        ];
    }
}
