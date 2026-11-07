<?php

namespace App\Modules\Compras\Requests;

use App\Modules\Compras\Models\OrdenDeCompra;
use Illuminate\Foundation\Http\FormRequest;

class EmitirOrdenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', OrdenDeCompra::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'cotizacion_id' => ['required', 'integer', 'exists:cotizaciones,id'],
        ];
    }
}
