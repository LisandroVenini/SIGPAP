<?php

namespace App\Modules\Produccion\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyOperarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('delete', $this->route('operario')) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
