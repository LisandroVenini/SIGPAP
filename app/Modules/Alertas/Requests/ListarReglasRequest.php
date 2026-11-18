<?php

namespace App\Modules\Alertas\Requests;

use App\Shared\Alertas\Enums\ModuloDeAlerta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListarReglasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'buscar' => ['nullable', 'string', 'max:120'],
            'modulo' => ['nullable', 'string', Rule::in(ModuloDeAlerta::values())],
            'activa' => ['nullable', 'boolean'],
        ];
    }
}
