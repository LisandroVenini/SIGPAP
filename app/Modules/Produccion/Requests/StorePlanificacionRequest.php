<?php

namespace App\Modules\Produccion\Requests;

use App\Modules\Produccion\Models\PlanificacionDeProduccion;
use Illuminate\Foundation\Http\FormRequest;

class StorePlanificacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PlanificacionDeProduccion::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'observaciones' => ['nullable', 'string'],
        ];
    }
}
