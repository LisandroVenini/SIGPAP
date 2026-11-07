<?php

namespace App\Modules\Produccion\Requests;

use App\Modules\Produccion\Models\PlanDeMantenimiento;
use Illuminate\Foundation\Http\FormRequest;

class ListarPlanesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', PlanDeMantenimiento::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'buscar' => ['nullable', 'string', 'max:255'],
        ];
    }
}
