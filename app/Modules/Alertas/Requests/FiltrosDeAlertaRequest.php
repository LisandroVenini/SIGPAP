<?php

namespace App\Modules\Alertas\Requests;

use App\Shared\Alertas\Enums\SeveridadAlerta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FiltrosDeAlertaRequest extends FormRequest
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
            'estado' => ['nullable', 'in:abiertas,todas'],
            'severidad' => ['nullable', Rule::in(SeveridadAlerta::values())],
            'tipo' => ['nullable', 'string'],
            'no_leidas' => ['nullable', 'boolean'],
        ];
    }
}
