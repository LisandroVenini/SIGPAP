<?php

namespace App\Modules\Compras\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSolicitudDisponibilidadRequest extends FormRequest
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
            'articulo_id' => ['required', 'integer', 'exists:articulos,id'],
            'cantidad' => ['required', 'numeric', 'gt:0', 'max:9999999'],
            'proveedor_id' => ['required', 'integer', 'exists:proveedores,id'],
            'fecha_requerida' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }
}
