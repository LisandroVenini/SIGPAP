<?php

namespace App\Modules\Produccion\Requests;

use App\Modules\Produccion\Models\Operario;
use Illuminate\Foundation\Http\FormRequest;

class ListarOperariosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Operario::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'buscar' => ['nullable', 'string', 'max:255'],
        ];
    }
}
