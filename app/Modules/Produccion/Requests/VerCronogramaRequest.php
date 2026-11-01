<?php

namespace App\Modules\Produccion\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerCronogramaRequest extends FormRequest
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
        return [];
    }
}
