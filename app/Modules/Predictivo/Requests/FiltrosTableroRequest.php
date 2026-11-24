<?php

namespace App\Modules\Predictivo\Requests;

use App\Modules\Produccion\Enums\EstadoOrdenProduccion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FiltrosTableroRequest extends FormRequest
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
            'estado' => ['nullable', 'string', Rule::in(EstadoOrdenProduccion::values())],
            'solo_riesgo' => ['nullable', 'boolean'],
        ];
    }
}
