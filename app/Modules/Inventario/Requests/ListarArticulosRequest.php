<?php

namespace App\Modules\Inventario\Requests;

use App\Modules\Inventario\Enums\EstadoArticulo;
use App\Modules\Inventario\Enums\TipoArticulo;
use App\Modules\Inventario\Models\Articulo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListarArticulosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Articulo::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'buscar' => ['nullable', 'string', 'max:255'],
            'tipo' => ['nullable', 'string', Rule::in(TipoArticulo::values())],
            'estado' => ['nullable', 'string', Rule::in(EstadoArticulo::values())],
        ];
    }
}
