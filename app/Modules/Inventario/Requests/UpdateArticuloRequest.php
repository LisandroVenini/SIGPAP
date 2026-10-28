<?php

namespace App\Modules\Inventario\Requests;

use App\Modules\Inventario\Enums\OrigenArticulo;
use App\Modules\Inventario\Enums\TipoArticulo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArticuloRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('articulo')) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'descripcion' => ['required', 'string', 'max:255'],
            'tipo' => ['required', 'string', Rule::in(TipoArticulo::values())],
            'origen' => [
                'nullable',
                'string',
                Rule::in(OrigenArticulo::values()),
                Rule::requiredIf(fn () => $this->input('tipo') === TipoArticulo::ProductoTerminado->value),
            ],
            'unidad_de_medida_id' => ['required', 'integer', 'exists:unidades_de_medida,id'],
            'stock_minimo' => ['nullable', 'numeric', 'min:0'],
            'stock_maximo' => ['nullable', 'numeric', 'min:0', 'gte:stock_minimo'],
        ];
    }
}
