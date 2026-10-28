<?php

namespace App\Modules\Inventario\Requests;

use App\Modules\Inventario\Enums\MotivoAjuste;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AjustarInventarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('ajustar', $this->route('articulo')) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nuevo_stock' => ['required', 'numeric', 'min:0'],
            'motivo' => ['required', 'string', Rule::in(MotivoAjuste::values())],
        ];
    }
}
