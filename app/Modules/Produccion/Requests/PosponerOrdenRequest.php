<?php

namespace App\Modules\Produccion\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PosponerOrdenRequest extends FormRequest
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
            'fecha_prevista_inicio' => ['nullable', 'date', 'after_or_equal:today'],
            'liberar_reservas' => ['required', 'boolean'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ];
    }
}
