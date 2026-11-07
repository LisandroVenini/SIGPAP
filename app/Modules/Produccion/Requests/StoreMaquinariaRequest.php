<?php

namespace App\Modules\Produccion\Requests;

use App\Modules\Produccion\Models\Maquinaria;
use Illuminate\Foundation\Http\FormRequest;

class StoreMaquinariaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Maquinaria::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'tipo' => ['required', 'string', 'max:255'],
        ];
    }
}
