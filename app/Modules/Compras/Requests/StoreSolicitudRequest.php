<?php

namespace App\Modules\Compras\Requests;

use App\Modules\Compras\Models\SolicitudDeCotizacion;
use Illuminate\Foundation\Http\FormRequest;

class StoreSolicitudRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SolicitudDeCotizacion::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'fecha_vencimiento' => ['required', 'date', 'after_or_equal:today'],
            'lineas' => ['required', 'array', 'min:1'],
            'lineas.*.articulo_id' => ['required', 'integer', 'exists:articulos,id'],
            'lineas.*.cantidad_solicitada' => ['required', 'numeric', 'gt:0'],
            'proveedores' => ['required', 'array', 'min:1'],
            'proveedores.*' => ['required', 'integer', 'distinct', 'exists:proveedores,id'],
        ];
    }
}
