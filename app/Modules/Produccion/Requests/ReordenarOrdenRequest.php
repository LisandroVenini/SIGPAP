<?php

namespace App\Modules\Produccion\Requests;

use App\Modules\Produccion\Enums\MotivoCambioPrioridad;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReordenarOrdenRequest extends FormRequest
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
            'prioridad' => ['required', 'integer', 'min:1'],
            'motivo' => ['required', Rule::enum(MotivoCambioPrioridad::class)],
            'detalle' => ['nullable', 'string', 'max:255'],
            'confirmar_conflicto' => ['sometimes', 'boolean'],
        ];
    }
}
