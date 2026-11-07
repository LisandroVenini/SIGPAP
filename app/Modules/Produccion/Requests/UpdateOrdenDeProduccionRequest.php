<?php

namespace App\Modules\Produccion\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrdenDeProduccionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('produccion.gestionar') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'fecha_entrega' => ['required', 'date'],
        ];
    }
}
