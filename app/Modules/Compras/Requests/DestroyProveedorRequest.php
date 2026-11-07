<?php

namespace App\Modules\Compras\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyProveedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('compras.gestionar') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
