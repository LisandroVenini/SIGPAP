<?php

namespace App\Modules\Compras\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmarOrdenRequest extends FormRequest
{
    /**
     * The token in the URL is the credential, so there is no user to authorize.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'aceptada' => ['required', 'boolean'],
            'motivo' => ['nullable', 'string', 'max:500', 'required_if:aceptada,false'],
        ];
    }
}
