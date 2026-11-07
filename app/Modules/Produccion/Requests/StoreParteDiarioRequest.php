<?php

namespace App\Modules\Produccion\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreParteDiarioRequest extends FormRequest
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
            'etapa_id' => ['required', 'integer'],
            'maquinaria_id' => ['nullable', 'integer'],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }
}
