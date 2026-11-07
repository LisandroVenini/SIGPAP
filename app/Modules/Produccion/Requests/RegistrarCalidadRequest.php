<?php

namespace App\Modules\Produccion\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarCalidadRequest extends FormRequest
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
            'parte_diario_id' => ['nullable', 'integer'],
            'cantidad_rechazada' => ['required', 'numeric', 'min:0'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ];
    }
}
