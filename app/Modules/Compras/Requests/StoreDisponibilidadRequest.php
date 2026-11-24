<?php

namespace App\Modules\Compras\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDisponibilidadRequest extends FormRequest
{
    /**
     * The token in the URL is the credential, so there is no user to authorize.
     */
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
            'cantidad_disponible' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'precio_unitario' => ['required', 'numeric', 'gt:0', 'max:9999999.99'],
            'fecha_prometida' => ['required', 'date', 'after_or_equal:today'],
        ];
    }
}
