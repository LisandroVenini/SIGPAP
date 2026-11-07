<?php

namespace App\Modules\Compras\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCotizacionRequest extends FormRequest
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
            'lineas' => ['required', 'array', 'min:1'],
            'lineas.*.articulo_id' => ['required', 'integer'],
            'lineas.*.precio_unitario' => ['required', 'numeric', 'gt:0', 'max:9999999.99'],
            'lineas.*.cantidad_ofertada' => ['required', 'numeric', 'gt:0', 'max:9999999.99'],
            'lineas.*.fecha_prometida' => ['required', 'date', 'after_or_equal:today'],
        ];
    }
}
