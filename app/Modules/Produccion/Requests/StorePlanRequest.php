<?php

namespace App\Modules\Produccion\Requests;

use App\Modules\Produccion\Models\PlanDeMantenimiento;
use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PlanDeMantenimiento::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'maquinaria_id' => ['required', 'integer', 'exists:maquinarias,id'],
            'umbral_uso' => ['required', 'numeric', 'gt:0'],
            'descripcion' => ['required', 'string', 'max:1000'],
        ];
    }
}
