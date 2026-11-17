<?php

namespace App\Modules\Inventario\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnularIngresoRequest extends FormRequest
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
        return [
            'numero_oc' => ['required', 'string'],
            'numero_remito' => ['required', 'string', 'max:255'],
        ];
    }
}
