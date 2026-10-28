<?php

namespace App\Modules\Inventario\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CambiarEstadoArticuloRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('cambiarEstado', $this->route('articulo')) ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'confirmar' => ['sometimes', 'boolean'],
        ];
    }
}
