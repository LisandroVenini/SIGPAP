<?php

namespace App\Modules\Produccion\Requests;

use App\Modules\Produccion\Models\Maquinaria;
use Illuminate\Foundation\Http\FormRequest;

class ListarMaquinariasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Maquinaria::class) ?? false;
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
