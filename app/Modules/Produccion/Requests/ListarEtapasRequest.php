<?php

namespace App\Modules\Produccion\Requests;

use App\Modules\Produccion\Models\Etapa;
use Illuminate\Foundation\Http\FormRequest;

class ListarEtapasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Etapa::class) ?? false;
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
