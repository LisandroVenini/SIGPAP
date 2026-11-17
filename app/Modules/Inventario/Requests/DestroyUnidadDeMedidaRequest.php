<?php

namespace App\Modules\Inventario\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyUnidadDeMedidaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventario.gestionar') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
