<?php

namespace App\Modules\Compras\Requests;

use App\Modules\Compras\Enums\EstadoOrdenDeCompra;
use App\Modules\Compras\Models\OrdenDeCompra;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListarOrdenesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', OrdenDeCompra::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'numero_oc' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'string', Rule::enum(EstadoOrdenDeCompra::class)],
            'proveedor' => ['nullable', 'string', 'max:255'],
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
        ];
    }
}
