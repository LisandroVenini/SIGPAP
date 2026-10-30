<?php

namespace App\Modules\Produccion\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreComponenteDeRecetaRequest extends FormRequest
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
            'articulo_insumo_id' => ['required', 'integer'],
            'cantidad' => ['required', 'numeric', 'gt:0', 'max:999999.99'],
        ];
    }
}
