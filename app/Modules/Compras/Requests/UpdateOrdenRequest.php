<?php

namespace App\Modules\Compras\Requests;

use App\Modules\Compras\Models\OrdenDeCompra;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrdenRequest extends FormRequest
{
    public function authorize(): bool
    {
        $orden = $this->route('orden');

        return $orden instanceof OrdenDeCompra
            && ($this->user()?->can('update', $orden) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'lineas' => ['required', 'array', 'min:1'],
            'lineas.*.articulo_id' => ['required', 'integer', 'exists:articulos,id'],
            'lineas.*.cantidad' => ['required', 'numeric', 'gt:0'],
            'lineas.*.precio_pactado' => ['required', 'numeric', 'min:0'],
        ];
    }
}
