<?php

namespace App\Modules\Compras\Requests;

use App\Modules\Compras\Models\OrdenDeCompra;
use Illuminate\Foundation\Http\FormRequest;

class AnularOrdenRequest extends FormRequest
{
    public function authorize(): bool
    {
        $orden = $this->route('orden');

        return $orden instanceof OrdenDeCompra
            && ($this->user()?->can('delete', $orden) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'motivo' => ['nullable', 'string', 'max:500'],
        ];
    }
}
